import 'dart:convert';
import 'dart:typed_data';
import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:healthyfood/dummy_data/dummy_data.dart';
import 'package:healthyfood/models/address.dart';
import 'package:healthyfood/models/cart_item.dart';
import 'package:healthyfood/models/credit_card.dart';
import 'package:healthyfood/models/notification_item.dart';
import 'package:healthyfood/models/order.dart';
import 'package:healthyfood/models/product.dart';
import 'package:healthyfood/services/api_service.dart';

class AppState extends ChangeNotifier {
  // --- User Profile State ---
  int _userId = -1;
  String _userName = "Ahmed Developer";
  String _userEmail = "ahmed@gmail.com";
  int _userAge = 25;
  String _userGender = "Male";
  double _userHeight = 175.0; // in cm
  double _userWeight = 70.0;  // in kg
  String _userPhone = "";
  String _userRole = "customer";
  String? _userAvatar;
  bool _isVerified = false;
  String _twofaMethod = "none";

  String _userFitnessGoal = "Maintain Weight";
  String _userActivityLevel = "Moderately Active";

  double _dailyCalorieGoal = 2200;
  double _caloriesConsumed = 0.0;
  String _selectedGoalType = "Maintenance";
  double _waterConsumed = 1.2;
  final double _waterGoal = 2.5;
  int _steps = 4500;
  final int _stepGoal = 10000;
  double _caloriesBurned = 320.0;

  int get userId => _userId;
  String get userName => _userName;
  String get userEmail => _userEmail;
  int get userAge => _userAge;
  String get userGender => _userGender;
  double get userHeight => _userHeight;
  double get userWeight => _userWeight;
  String get userPhone => _userPhone;
  String get userRole => _userRole;
  static String get defaultAvatarUrl => "${ApiService.baseUrl}/assets/images/default_avatar.png";
  String get userAvatar => (_userAvatar == null || _userAvatar!.isEmpty) ? defaultAvatarUrl : _userAvatar!;
  bool get isVerified => _isVerified;
  String get twofaMethod => _twofaMethod;

  String get userFitnessGoal => _userFitnessGoal;
  String get userActivityLevel => _userActivityLevel;

  double get dailyCalorieGoal => _dailyCalorieGoal;
  double get caloriesConsumed => _caloriesConsumed;

  String get selectedGoalType => _selectedGoalType;

  double get selectedGoalCalories {
    switch (_selectedGoalType) {
      case "Weight Loss":
        return weightLossCalories;
      case "Weight Gain":
        return weightGainCalories;
      case "Maintenance":
      default:
        return maintenanceCalories;
    }
  }

  void setSelectedGoalType(String type) {
    _selectedGoalType = type;
    _dailyCalorieGoal = selectedGoalCalories;
    notifyListeners();
  }

  void resetCaloriesConsumed() {
    _caloriesConsumed = 0.0;
    notifyListeners();
  }
  double get waterConsumed => _waterConsumed;
  double get waterGoal => _waterGoal;
  int get steps => _steps;
  int get stepGoal => _stepGoal;
  double get caloriesBurned => _caloriesBurned;

  // --- Calorie Calculation & Macro Distribution ---

  double get maintenanceCalories {
    double bmr;
    if (_userGender.toLowerCase() == 'male' || _userGender.toLowerCase() == 'm') {
      bmr = 10 * _userWeight + 6.25 * _userHeight - 5 * _userAge + 5;
    } else {
      bmr = 10 * _userWeight + 6.25 * _userHeight - 5 * _userAge - 161;
    }
    
    double multiplier = 1.375;
    final activity = _userActivityLevel.toLowerCase();
    if (activity.contains('sedentary')) {
      multiplier = 1.2;
    } else if (activity.contains('light')) multiplier = 1.375;
    else if (activity.contains('moderate')) multiplier = 1.55;
    else if (activity.contains('very')) multiplier = 1.725;
    else if (activity.contains('extra')) multiplier = 1.9;

    return bmr * multiplier;
  }

  double get weightLossCalories => maintenanceCalories - 500;
  double get weightGainCalories => maintenanceCalories + 500;

  Map<String, int> getMacrosForCalories(double calories, {double proteinPct = 0.30, double carbPct = 0.40, double fatPct = 0.30}) {
    int protein = ((calories * proteinPct) / 4).round();
    int carbs = ((calories * carbPct) / 4).round();
    int fats = ((calories * fatPct) / 9).round();
    return {'protein': protein, 'carbs': carbs, 'fats': fats};
  }

  Map<String, int> get maintenanceMacros => getMacrosForCalories(maintenanceCalories, proteinPct: 0.30, carbPct: 0.40, fatPct: 0.30);
  Map<String, int> get weightLossMacros => getMacrosForCalories(weightLossCalories, proteinPct: 0.35, carbPct: 0.35, fatPct: 0.30);
  Map<String, int> get weightGainMacros => getMacrosForCalories(weightGainCalories, proteinPct: 0.25, carbPct: 0.50, fatPct: 0.25);

  // --- Add-ons & Recommendations ---
  List<Product> _addOns = [];
  List<Product> get addOns => _addOns;

  List<Product> _recommendations = [];
  List<Product> get recommendations => _recommendations;

  AppState() {
    fetchAndSetProducts();
  }

  Future<void> fetchAndSetAddOns() async {
    try {
      final list = await ApiService.fetchAddOns();
      _addOns = list.map((item) => Product.fromJson(item)).toList();
      for (var food in foods) {
        food.addOns = _addOns;
      }
      notifyListeners();
    } catch (e) {
      debugPrint("Error fetching addons: $e");
    }
  }

  Future<void> fetchRecommendations({String? filterType}) async {
    if (_userId <= 0) return;
    try {
      final list = await ApiService.fetchRecommendations(_userId, filterType: filterType);
      _recommendations = list.map((item) => Product.fromJson(item)).toList();
      notifyListeners();
    } catch (e) {
      debugPrint("Error fetching recommendations: $e");
    }
  }

  // --- Session Persistence using SharedPreferences ---

  Future<void> saveSessionToPrefs() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final sessionData = json.encode({
        'id': _userId,
        'username': _userName,
        'email': _userEmail,
        'role': _userRole,
        'twofa_method': _twofaMethod,
        'is_verified': _isVerified,
        'profile': {
          'age': _userAge,
          'weight': _userWeight,
          'height': _userHeight,
          'gender': _userGender,
          'phone': _userPhone,
          'avatar': _userAvatar,
          'fitness_goal': _userFitnessGoal,
          'activity_level': _userActivityLevel,
          'calorie_target': _dailyCalorieGoal,
        }
      });
      await prefs.setString('user_session', sessionData);
    } catch (e) {
      debugPrint("Error saving session to preferences: $e");
    }
  }

  Future<void> clearSessionFromPrefs() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      await prefs.remove('user_session');
    } catch (e) {
      debugPrint("Error clearing session from preferences: $e");
    }
  }

  Future<bool> tryAutoLogin() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      if (!prefs.containsKey('user_session')) return false;

      final sessionDataString = prefs.getString('user_session');
      if (sessionDataString == null || sessionDataString.isEmpty) return false;

      final sessionData = json.decode(sessionDataString) as Map<String, dynamic>;
      
      _userId = int.tryParse(sessionData['id']?.toString() ?? '') ?? -1;
      _userName = sessionData['username']?.toString() ?? "User";
      _userEmail = sessionData['email']?.toString() ?? "";
      _userRole = sessionData['role']?.toString() ?? "customer";
      _twofaMethod = sessionData['twofa_method']?.toString() ?? "none";
      _isVerified = sessionData['is_verified']?.toString() == 'true' || sessionData['is_verified'] == 1 || sessionData['is_verified'] == true;

      final profile = sessionData['profile'];
      if (profile != null) {
        _userAge = int.tryParse(profile['age']?.toString() ?? '') ?? 25;
        _userWeight = profile['weight'] != null ? (profile['weight'] as num).toDouble() : 70.0;
        _userHeight = profile['height'] != null ? (profile['height'] as num).toDouble() : 175.0;
        _userGender = profile['gender']?.toString() ?? 'Male';
        _userPhone = profile['phone']?.toString() ?? '';
        if (profile['avatar'] != null) {
          final avatarUrl = profile['avatar'].toString();
          _userAvatar = avatarUrl.startsWith('http')
              ? "$avatarUrl?v=${DateTime.now().millisecondsSinceEpoch}"
              : avatarUrl;
        } else {
          _userAvatar = null;
        }
        _userFitnessGoal = profile['fitness_goal']?.toString() ?? 'Maintain Weight';
        _userActivityLevel = profile['activity_level']?.toString() ?? 'Moderately Active';
        _dailyCalorieGoal = profile['calorie_target'] != null ? (profile['calorie_target'] as num).toDouble() : 2000.0;
      }

      // Synchronize database records in the background from APIs concurrently
      Future.wait([
        fetchAndSetCart(),
        fetchAndSetAddresses(),
        fetchAndSetOrders(),
        fetchRecommendations(),
      ]).catchError((e) {
        debugPrint("Error during background synchronization: $e");
        return [];
      });

      notifyListeners();
      return true;
    } catch (e) {
      debugPrint("Error reading session from preferences: $e");
      return false;
    }
  }

  Future<void> fetchAndSetProducts() async {
    try {
      final list = await ApiService.fetchProducts();
      final List<Product> loadedProducts = list.map((item) => Product.fromJson(item)).toList();
      foods = loadedProducts.where((p) => p.type.toLowerCase() == 'food' || p.type.toLowerCase() == 'meal' || p.type.toLowerCase() == 'protein').toList();
      drinks = loadedProducts.where((p) => p.type.toLowerCase() == 'drink' || p.type.toLowerCase() == 'juice').toList();
      
      // Fetch addons from the dedicated add-ons API
      try {
        final addOnsList = await ApiService.fetchAddOns();
        _addOns = addOnsList.map((item) => Product.fromJson(item)).toList();
      } catch (addonsError) {
        debugPrint("Error fetching addons: $addonsError");
        _addOns = [];
      }
      
      // Link addons to foods
      for (var food in foods) {
        food.addOns = _addOns;
      }

      if (loadedProducts.isEmpty) {
        foods = dummyFoods;
        drinks = dummyDrinks;
      }
      notifyListeners();
    } catch (e) {
      debugPrint("Error fetching products: $e");
      foods = dummyFoods;
      drinks = dummyDrinks;
      _addOns = [];
    }
  }

  // Login Method updated to fetch addresses, cart, and orders
  Future<void> login(String identity, String password) async {
    final response = await ApiService.login(identity, password);
    if (response['success'] == true) {
      final user = response['user'];

      _userId = int.tryParse(user['id']?.toString() ?? '') ?? -1;
      _userName = user['username']?.toString() ?? "User";
      _userEmail = user['email']?.toString() ?? "";
      _userRole = user['role']?.toString() ?? "customer";
      _twofaMethod = user['twofa_method']?.toString() ?? "none";
      _isVerified = user['is_verified']?.toString() == '1' || user['is_verified'] == true;

      final profile = user['profile'];
      if (profile != null) {
        _userAge = int.tryParse(profile['age']?.toString() ?? '') ?? 25;
        _userWeight = profile['weight'] != null ? (profile['weight'] as num).toDouble() : 70.0;
        _userHeight = profile['height'] != null ? (profile['height'] as num).toDouble() : 175.0;
        _userGender = profile['gender']?.toString() ?? 'Male';
        _userPhone = profile['phone']?.toString() ?? '';
        if (profile['avatar'] != null) {
          final avatarUrl = profile['avatar'].toString();
          _userAvatar = avatarUrl.startsWith('http')
              ? "$avatarUrl?v=${DateTime.now().millisecondsSinceEpoch}"
              : avatarUrl;
        } else {
          _userAvatar = null;
        }
        _userFitnessGoal = profile['fitness_goal']?.toString() ?? 'Maintain Weight';
        _userActivityLevel = profile['activity_level']?.toString() ?? 'Moderately Active';
        _dailyCalorieGoal = profile['calorie_target'] != null ? (profile['calorie_target'] as num).toDouble() : 2000.0;
      }

      // Synchronize database records after successful login in parallel
      await Future.wait([
        fetchAndSetCart(),
        fetchAndSetAddresses(),
        fetchAndSetOrders(),
        fetchRecommendations(),
      ]);
      await saveSessionToPrefs();

      notifyListeners();
      addNotification("Logged In", "Welcome back, $_userName!");
    } else {
      throw Exception(response['error'] ?? 'Login failed');
    }
  }

  Future<void> register(String username, String email, String password) async {
    await ApiService.register(username, email, password);
  }

  double get userBMI {
    double heightInMeters = _userHeight / 100;
    if (heightInMeters == 0) return 0;
    return _userWeight / (heightInMeters * heightInMeters);
  }

  final List<Map<String, dynamic>> _bmiHistory = [
    {"date": "2026-05-01", "bmi": 22.5},
    {"date": "2026-05-05", "bmi": 22.1},
    {"date": "2026-05-10", "bmi": 21.8},
  ];
  List<Map<String, dynamic>> get bmiHistory => _bmiHistory;

  Future<void> updateProfile({
    String? name,
    int? age,
    double? height,
    double? weight,
    String? phone,
    String? gender,
    String? fitnessGoal,
    String? activityLevel,
    Uint8List? avatarBytes,
    String? avatarFileName,
  }) async {
    final updatedName = name ?? _userName;
    final updatedPhone = phone ?? _userPhone;
    final updatedAge = age ?? _userAge;
    final updatedWeight = weight ?? _userWeight;
    final updatedHeight = height ?? _userHeight;
    final updatedGender = gender ?? _userGender;
    final updatedFitnessGoal = fitnessGoal ?? _userFitnessGoal;
    final updatedActivityLevel = activityLevel ?? _userActivityLevel;

    if (_userId > 0) {
      final res = await ApiService.updateProfile(
        userId: _userId,
        username: updatedName,
        phone: updatedPhone,
        age: updatedAge,
        weight: updatedWeight,
        height: updatedHeight,
        gender: updatedGender,
        fitnessGoal: updatedFitnessGoal,
        activityLevel: updatedActivityLevel,
        avatarBytes: avatarBytes,
        avatarFileName: avatarFileName,
      );

      if (res['success'] == true) {
        final profile = res['profile'];
        if (profile != null) {
          _userName = profile['username']?.toString() ?? updatedName;
          _userPhone = profile['phone']?.toString() ?? updatedPhone;
          _userAge = int.tryParse(profile['age']?.toString() ?? '') ?? updatedAge;
          _userWeight = profile['weight'] != null ? (profile['weight'] as num).toDouble() : updatedWeight;
          _userHeight = profile['height'] != null ? (profile['height'] as num).toDouble() : updatedHeight;
          _userGender = profile['gender']?.toString() ?? updatedGender;
          _userFitnessGoal = profile['fitness_goal']?.toString() ?? updatedFitnessGoal;
          _userActivityLevel = profile['activity_level']?.toString() ?? updatedActivityLevel;
          _dailyCalorieGoal = profile['calorie_target'] != null ? (profile['calorie_target'] as num).toDouble() : 2000.0;
          if (profile['avatar'] != null) {
            final avatarUrl = profile['avatar'].toString();
            _userAvatar = avatarUrl.startsWith('http')
                ? "$avatarUrl?v=${DateTime.now().millisecondsSinceEpoch}"
                : avatarUrl;
          }
        }
      }
    } else {
      _userName = updatedName;
      _userAge = updatedAge;
      _userHeight = updatedHeight;
      _userWeight = updatedWeight;
      _userPhone = updatedPhone;
      _userGender = updatedGender;
      _userFitnessGoal = updatedFitnessGoal;
      _userActivityLevel = updatedActivityLevel;
      _dailyCalorieGoal = _calculateLocalCalorieGoal(updatedWeight, updatedHeight, updatedAge, updatedGender, updatedFitnessGoal, updatedActivityLevel);
    }

    _bmiHistory.add({
      "date": DateTime.now().toString().split(' ')[0],
      "bmi": double.parse(userBMI.toStringAsFixed(1)),
    });

    await saveSessionToPrefs();

    notifyListeners();
    addNotification("Profile Updated", "Your profile and BMI have been successfully updated.");
  }

  double _calculateLocalCalorieGoal(double weight, double height, int age, String gender, String goal, String activity) {
    double bmr;
    if (gender.toLowerCase() == 'male' || gender.toLowerCase() == 'm') {
      bmr = 10 * weight + 6.25 * height - 5 * age + 5;
    } else {
      bmr = 10 * weight + 6.25 * height - 5 * age - 161;
    }
    double multiplier = 1.375;
    if (activity.toLowerCase().contains('sedentary')) {
      multiplier = 1.2;
    } else if (activity.toLowerCase().contains('light')) multiplier = 1.375;
    else if (activity.toLowerCase().contains('moderate')) multiplier = 1.55;
    else if (activity.toLowerCase().contains('very')) multiplier = 1.725;
    else if (activity.toLowerCase().contains('extra')) multiplier = 1.9;

    double tdee = bmr * multiplier;
    if (goal.toLowerCase().contains('lose')) return tdee - 500;
    if (goal.toLowerCase().contains('gain')) return tdee + 500;
    return tdee;
  }

  Future<void> updateEmail(String newEmail) async {
    if (_userId > 0) {
      final res = await ApiService.changeEmail(_userId, newEmail);
      if (res['success'] != true) {
        throw Exception(res['error'] ?? 'Failed to update email');
      }
    }
    _userEmail = newEmail;
    notifyListeners();
    addNotification("Email Updated", "Your contact email has been changed to $newEmail.");
  }

  Future<void> changePassword(String currentPassword, String newPassword) async {
    if (_userId > 0) {
      final res = await ApiService.changePassword(
        userId: _userId,
        currentPassword: currentPassword,
        newPassword: newPassword,
      );
      if (res['success'] != true) {
        throw Exception(res['error'] ?? 'Failed to change password');
      }
    }
    addNotification("Password Updated", "Your password has been changed successfully.");
  }

  Future<void> deleteAccount() async {
    if (_userId > 0) {
      final res = await ApiService.deleteAccount(_userId);
      if (res['success'] == true) {
        logout();
      } else {
        throw Exception(res['error'] ?? 'Failed to delete account');
      }
    } else {
      logout();
    }
  }

  Future<void> logout() async {
    _userId = -1;
    _userName = "Guest User";
    _userEmail = "";
    _userAge = 25;
    _userGender = "Male";
    _userHeight = 175.0;
    _userWeight = 70.0;
    _userPhone = "";
    _userRole = "customer";
    _userAvatar = null;
    _isVerified = false;
    _twofaMethod = "none";
    _userFitnessGoal = "Maintain Weight";
    _userActivityLevel = "Moderately Active";
    _dailyCalorieGoal = 2000;
    _caloriesConsumed = 0;
    _waterConsumed = 0;
    _steps = 0;
    _caloriesBurned = 0;
    _cartItems.clear();
    _orders.clear();
    _addresses.clear();
    _favorites.clear();
    _notifications.clear();
    _recommendations.clear();
    await clearSessionFromPrefs();
    notifyListeners();
  }

  void updateCalorieGoal(double newGoal) {
    _dailyCalorieGoal = newGoal;
    notifyListeners();
  }

  void addWater(int amount) {
    _waterConsumed += amount;
    notifyListeners();
  }

  void addSteps(int amount) {
    _steps += amount;
    notifyListeners();
  }

  void addBurnedCalories(double amount) {
    _caloriesBurned += amount;
    notifyListeners();
  }

  void addConsumedCalories(int calories) {
    _caloriesConsumed += calories;
    notifyListeners();
  }

  // --- Products Data ---
  List<Product> foods = dummyFoods;
  List<Product> drinks = dummyDrinks;

  List<Product> get allProducts => [...foods, ...drinks];

  // --- Favorites Data ---
  final List<Product> _favorites = [];
  List<Product> get favorites => _favorites;

  bool isFavorite(Product product) => _favorites.any((p) => p.id == product.id);

  void toggleFavorite(Product product) {
    if (isFavorite(product)) {
      _favorites.removeWhere((p) => p.id == product.id);
      addNotification("Removed from Favorites", "${product.name} removed from your favorites.");
    } else {
      _favorites.add(product);
      addNotification("Added to Favorites", "${product.name} added to your favorites.");
    }
    notifyListeners();
  }

  // --- Cart Data ---
  final List<CartItem> _cartItems = [];
  List<CartItem> get cartItems => _cartItems;

  int get cartItemCount => _cartItems.fold(0, (sum, item) => sum + item.quantity);
  double get cartSubtotal => _cartItems.fold(0, (sum, item) => sum + (item.product.price * item.quantity));
  double get cartDelivery => _cartItems.isEmpty ? 0 : 50.0;
  double get cartTotal => cartSubtotal + cartDelivery;
  int get cartTotalCalories => _cartItems.fold(0, (sum, item) => sum + (item.product.calories * item.quantity));

  Future<void> fetchAndSetCart() async {
    if (_userId <= 0) return;
    try {
      final res = await ApiService.getCart(_userId);
      if (res['success'] == true) {
        _updateLocalCart(res['cartItems']);
      }
    } catch (e) {
      debugPrint("Error fetching cart: $e");
    }
  }

  void _updateLocalCart(List<dynamic>? items) {
    _cartItems.clear();
    if (items != null) {
      for (var item in items) {
        if (item == null || item['product'] == null) continue;
        _cartItems.add(CartItem(
          product: Product.fromJson(item['product']),
          quantity: item['quantity'] != null ? (item['quantity'] as num).toInt() : 1,
        ));
      }
    }
    notifyListeners();
  }

  Future<void> addToCart(Product product) async {
    final prodId = int.tryParse(product.id) ?? 0;
    if (_userId > 0 && prodId > 0) {
      try {
        final res = await ApiService.addToCart(_userId, prodId, 1);
        if (res['success'] == true) {
          _updateLocalCart(res['cartItems']);
        }
      } catch (e) {
        debugPrint("Error adding to cart: $e");
      }
    } else {
      final existingIndex = _cartItems.indexWhere((item) => item.product.id == product.id);
      if (existingIndex >= 0) {
        _cartItems[existingIndex].quantity += 1;
      } else {
        _cartItems.add(CartItem(product: product, quantity: 1));
      }
      notifyListeners();
    }
    addNotification("Added to Cart", "${product.name} added to your cart.");
  }

  Future<void> updateQuantity(Product product, int quantity) async {
    if (quantity <= 0) {
      await removeFromCart(product);
      return;
    }
    final prodId = int.tryParse(product.id) ?? 0;
    if (_userId > 0 && prodId > 0) {
      try {
        final res = await ApiService.updateCartQuantity(_userId, prodId, quantity);
        if (res['success'] == true) {
          _updateLocalCart(res['cartItems']);
        }
      } catch (e) {
        debugPrint("Error updating quantity: $e");
      }
    } else {
      final existingIndex = _cartItems.indexWhere((item) => item.product.id == product.id);
      if (existingIndex >= 0) {
        _cartItems[existingIndex].quantity = quantity;
        notifyListeners();
      }
    }
  }

  Future<void> removeFromCart(Product product) async {
    final prodId = int.tryParse(product.id) ?? 0;
    if (_userId > 0 && prodId > 0) {
      try {
        final res = await ApiService.removeFromCart(_userId, prodId);
        if (res['success'] == true) {
          _updateLocalCart(res['cartItems']);
        }
      } catch (e) {
        debugPrint("Error removing from cart: $e");
      }
    } else {
      _cartItems.removeWhere((item) => item.product.id == product.id);
      notifyListeners();
    }
    addNotification("Removed from Cart", "${product.name} removed from your cart.");
  }

  Future<void> clearCart() async {
    if (_userId > 0) {
      try {
        final res = await ApiService.clearCart(_userId);
        if (res['success'] == true) {
          _updateLocalCart(res['cartItems']);
        }
      } catch (e) {
        debugPrint("Error clearing cart: $e");
      }
    } else {
      _cartItems.clear();
      notifyListeners();
    }
  }

  // --- Orders Data ---
  final List<OrderModel> _orders = [];
  List<OrderModel> get orders => _orders;

  Future<void> fetchAndSetOrders() async {
    if (_userId <= 0) return;
    try {
      final res = await ApiService.fetchOrders(_userId);
      if (res['success'] == true) {
        _updateLocalOrders(res['orders']);
      }
    } catch (e) {
      debugPrint("Error fetching orders: $e");
    }
  }

  void _updateLocalOrders(List<dynamic>? ordList) {
    _orders.clear();
    if (ordList != null) {
      for (var ord in ordList) {
        if (ord == null) continue;
        final items = ord['items'] as List<dynamic>? ?? const [];
        final List<CartItem> loadedItems = [];
        for (var item in items) {
          if (item == null || item['product'] == null) continue;
          loadedItems.add(CartItem(
            product: Product.fromJson(item['product']),
            quantity: item['quantity'] != null ? (item['quantity'] as num).toInt() : 1,
          ));
        }
        _orders.add(OrderModel(
          id: ord['id']?.toString() ?? '',
          total: ord['total'] != null ? (ord['total'] as num).toDouble() : 0.0,
          status: ord['status']?.toString() ?? 'Pending',
          date: DateTime.tryParse(ord['date']?.toString() ?? '') ?? DateTime.now(),
          items: loadedItems,
        ));
      }
    }
    notifyListeners();
  }

  Future<String?> checkout() async {
    if (_cartItems.isEmpty) return null;

    final address = selectedAddress;
    final addressDesc = address != null ? address.formattedAddress : "No Address Provided";
    final addressPhone = address != null ? address.phoneNumber : _userPhone;
    final isCash = _selectedPaymentMethodId == 'CASH';
    final paymentMethod = isCash ? 'cod' : 'stripe';

    // Map cart items to the JSON format expected by checkout.php
    final mappedItems = _cartItems.map((item) => {
      'product_id': int.tryParse(item.product.id) ?? 0,
      'quantity': item.quantity,
    }).toList();

    if (_userId > 0) {
      try {
        final res = await ApiService.checkout(
          userId: _userId,
          location: addressDesc,
          phone: addressPhone,
          paymentMethod: paymentMethod,
          cartItems: mappedItems,
        );

        if (res['success'] == true) {
          // Refresh orders from server
          await fetchAndSetOrders();
          // Refresh recommendations (since purchase history updated)
          await fetchRecommendations();
          
          _cartItems.clear();
          addConsumedCalories(cartTotalCalories);
          final orderIdStr = res['orderId']?.toString();
          addNotification("Order Placed", "Your order $orderIdStr has been successfully placed!");
          notifyListeners();
          return orderIdStr;
        } else {
          throw Exception(res['error'] ?? 'Checkout failed');
        }
      } catch (e) {
        debugPrint("Error checking out: $e");
        rethrow;
      }
    } else {
      await Future.delayed(const Duration(seconds: 2));
      final newOrder = OrderModel(
        id: "ORD-${DateTime.now().millisecondsSinceEpoch}",
        items: List.from(_cartItems),
        total: cartTotal,
        date: DateTime.now(),
      );
      _orders.insert(0, newOrder);
      addConsumedCalories(cartTotalCalories);
      addNotification("Order Placed", "Your order ${newOrder.id} has been successfully placed!");
      clearCart();
      return newOrder.id;
    }
  }

  Future<Map<String, dynamic>> cancelOrder(String orderId) async {
    if (_userId > 0) {
      try {
        final res = await ApiService.cancelOrder(orderId: orderId, userId: _userId);
        if (res['success'] == true) {
          // Update local status of the order to 'cancelled'
          final index = _orders.indexWhere((ord) => ord.id == orderId || ord.id == "ORD-$orderId" || "ORD-${ord.id}" == orderId);
          if (index != -1) {
            _orders[index] = OrderModel(
              id: _orders[index].id,
              total: _orders[index].total,
              status: 'cancelled',
              date: _orders[index].date,
              items: _orders[index].items,
            );
          } else {
            // Re-fetch from server to be sure
            await fetchAndSetOrders();
          }
          addNotification("Order Cancelled", "Your order $orderId has been cancelled.");
          notifyListeners();
          return res;
        } else {
          return {"success": false, "error": res['error'] ?? 'Cancellation failed'};
        }
      } catch (e) {
        debugPrint("Error cancelling order: $e");
        return {"success": false, "error": e.toString()};
      }
    } else {
      // Local order mode cancellation
      final index = _orders.indexWhere((ord) => ord.id == orderId);
      if (index != -1) {
        _orders[index] = OrderModel(
          id: _orders[index].id,
          total: _orders[index].total,
          status: 'cancelled',
          date: _orders[index].date,
          items: _orders[index].items,
        );
        addNotification("Order Cancelled", "Your order $orderId has been cancelled.");
        notifyListeners();
        return {"success": true, "message": "Order cancelled successfully"};
      }
      return {"success": false, "error": "Order not found"};
    }
  }

  // --- Notifications Data ---
  final List<NotificationItem> _notifications = [];
  List<NotificationItem> get notifications => _notifications;
  int get unreadNotificationsCount => _notifications.where((n) => !n.isRead).length;

  void addNotification(String title, String message) {
    _notifications.insert(0, NotificationItem(
      id: DateTime.now().toString(),
      title: title,
      message: message,
      date: DateTime.now(),
    ));
    notifyListeners();
  }

  void markNotificationsAsRead() {
    for (var n in _notifications) {
      n.isRead = true;
    }
    notifyListeners();
  }

  // --- Payment State ---
  final List<CreditCard> _creditCards = [];
  List<CreditCard> get creditCards => _creditCards;

  String _selectedPaymentMethodId = 'CASH';
  String get selectedPaymentMethodId => _selectedPaymentMethodId;

  CreditCard? get selectedCreditCard {
    if (_selectedPaymentMethodId == 'CASH') return null;
    try {
      return _creditCards.firstWhere((card) => card.id == _selectedPaymentMethodId);
    } catch (_) {
      return null;
    }
  }

  void selectPaymentMethod(String id) {
    _selectedPaymentMethodId = id;
    notifyListeners();
  }

  void addCreditCard(String cardNumber, String cardHolderName, String expiryDate, String cvv) {
    final newCard = CreditCard(
      id: DateTime.now().millisecondsSinceEpoch.toString(),
      cardNumber: cardNumber,
      cardHolderName: cardHolderName,
      expiryDate: expiryDate,
      cvv: cvv,
    );
    _creditCards.add(newCard);
    _selectedPaymentMethodId = newCard.id;
    notifyListeners();
  }

  void updateCreditCard(String id, String cardNumber, String cardHolderName, String expiryDate, String cvv) {
    final index = _creditCards.indexWhere((c) => c.id == id);
    if (index != -1) {
      _creditCards[index] = CreditCard(
        id: id,
        cardNumber: cardNumber,
        cardHolderName: cardHolderName,
        expiryDate: expiryDate,
        cvv: cvv,
      );
      notifyListeners();
    }
  }

  void deleteCreditCard(String id) {
    _creditCards.removeWhere((c) => c.id == id);
    if (_selectedPaymentMethodId == id) {
      _selectedPaymentMethodId = 'CASH';
    }
    notifyListeners();
  }

  // --- Address Data ---
  final List<Address> _addresses = [];
  List<Address> get addresses => _addresses;
  Address? get selectedAddress => _addresses.where((a) => a.isSelected).firstOrNull;

  Future<void> fetchAndSetAddresses() async {
    if (_userId <= 0) return;
    try {
      final res = await ApiService.fetchAddresses(_userId);
      if (res['success'] == true) {
        _updateLocalAddresses(res['addresses']);
      }
    } catch (e) {
      debugPrint("Error fetching addresses: $e");
    }
  }

  void _updateLocalAddresses(List<dynamic>? addrList) {
    _addresses.clear();
    if (addrList != null) {
      for (var addr in addrList) {
        if (addr == null) continue;
        _addresses.add(Address(
          id: addr['id']?.toString() ?? '',
          type: addr['type']?.toString() ?? 'Home',
          streetAddress: addr['streetAddress']?.toString() ?? '',
          aptNumber: addr['aptNumber']?.toString() ?? '',
          floor: addr['floor']?.toString() ?? '',
          phoneNumber: addr['phoneNumber']?.toString() ?? '',
          isSelected: addr['isSelected'] == true || addr['isSelected'] == 1 || addr['isSelected']?.toString() == '1',
        ));
      }
    }
    notifyListeners();
  }

  Future<void> selectAddress(String id) async {
    final addrId = int.tryParse(id) ?? 0;
    if (_userId > 0 && addrId > 0) {
      try {
        final res = await ApiService.selectAddress(_userId, addrId);
        if (res['success'] == true) {
          _updateLocalAddresses(res['addresses']);
        }
      } catch (e) {
        debugPrint("Error selecting address: $e");
      }
    } else {
      for (var address in _addresses) {
        address.isSelected = address.id == id;
      }
      notifyListeners();
    }
  }

  Future<void> addAddress(String type, String streetAddress, String aptNumber, String floor, String phoneNumber) async {
    if (_userId > 0) {
      try {
        final res = await ApiService.addAddress(
          userId: _userId,
          type: type,
          streetAddress: streetAddress,
          aptNumber: aptNumber,
          floor: floor,
          phoneNumber: phoneNumber,
        );
        if (res['success'] == true) {
          _updateLocalAddresses(res['addresses']);
        }
      } catch (e) {
        debugPrint("Error adding address: $e");
      }
    } else {
      final newAddress = Address(
        id: DateTime.now().millisecondsSinceEpoch.toString(),
        type: type,
        streetAddress: streetAddress,
        aptNumber: aptNumber,
        floor: floor,
        phoneNumber: phoneNumber,
        isSelected: _addresses.isEmpty,
      );
      _addresses.add(newAddress);
      notifyListeners();
    }
  }

  Future<void> updateAddress(String id, String type, String streetAddress, String aptNumber, String floor, String phoneNumber) async {
    final addrId = int.tryParse(id) ?? 0;
    if (_userId > 0 && addrId > 0) {
      try {
        final res = await ApiService.updateAddress(
          userId: _userId,
          addressId: addrId,
          type: type,
          streetAddress: streetAddress,
          aptNumber: aptNumber,
          floor: floor,
          phoneNumber: phoneNumber,
        );
        if (res['success'] == true) {
          _updateLocalAddresses(res['addresses']);
        }
      } catch (e) {
        debugPrint("Error updating address: $e");
      }
    } else {
      final index = _addresses.indexWhere((a) => a.id == id);
      if (index != -1) {
        final isSelected = _addresses[index].isSelected;
        _addresses[index] = Address(
          id: id,
          type: type,
          streetAddress: streetAddress,
          aptNumber: aptNumber,
          floor: floor,
          phoneNumber: phoneNumber,
          isSelected: isSelected,
        );
        notifyListeners();
      }
    }
  }

  Future<void> deleteAddress(String id) async {
    final addrId = int.tryParse(id) ?? 0;
    if (_userId > 0 && addrId > 0) {
      try {
        final res = await ApiService.deleteAddress(_userId, addrId);
        if (res['success'] == true) {
          _updateLocalAddresses(res['addresses']);
        }
      } catch (e) {
        debugPrint("Error deleting address: $e");
      }
    } else {
      _addresses.removeWhere((a) => a.id == id);
      if (_addresses.isNotEmpty && !_addresses.any((a) => a.isSelected)) {
        _addresses.first.isSelected = true;
      }
      notifyListeners();
    }
  }

  // --- Bottom Navigation State ---
  int currentTabIndex = 0;
  void setTabIndex(int index) {
    currentTabIndex = index;
    notifyListeners();
  }

  // --- Dispose Safety to Prevent memory-leak exceptions ---
  bool _disposed = false;

  @override
  void dispose() {
    _disposed = true;
    super.dispose();
  }

  @override
  void notifyListeners() {
    if (!_disposed) {
      super.notifyListeners();
    }
  }
}