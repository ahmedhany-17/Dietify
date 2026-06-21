import 'dart:convert';
import 'dart:typed_data';
import 'package:http/http.dart' as http;

class ApiService {
  // Base URL (change to http://10.0.2.2/healthyfood if running on Android Emulator)
  static const String baseUrl = "http://localhost/healthyfood";

  // Helper to execute POST requests with timeout and error handling
  static Future<dynamic> _post(String path, Map<String, dynamic> body) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/$path'),
        headers: {'Content-Type': 'application/json'},
        body: json.encode(body),
      ).timeout(const Duration(seconds: 10));
      return _handleResponse(response);
    } catch (e) {
      throw Exception('Network or Server connection error: $e');
    }
  }

  // Helper to parse JSON responses safely
  static dynamic _handleResponse(http.Response response) {
    if (response.statusCode == 200) {
      try {
        return json.decode(response.body);
      } catch (e) {
        throw FormatException('Failed to parse response JSON: $e. Body: ${response.body}');
      }
    } else {
      throw Exception('Server returned status code: ${response.statusCode}');
    }
  }

  // 1. Fetch Products
  static Future<List<dynamic>> fetchProducts() async {
    try {
      final response = await http.get(Uri.parse('$baseUrl/products_api.php'))
          .timeout(const Duration(seconds: 10));
      if (response.statusCode == 200) {
        final decoded = json.decode(response.body);
        return decoded is List<dynamic> ? decoded : [];
      } else {
        throw Exception('Failed to load products: status ${response.statusCode}');
      }
    } catch (e) {
      throw Exception('Error loading products: $e');
    }
  }

  // 2. Authentication: Login
  static Future<Map<String, dynamic>> login(String identity, String password) async {
    try {
      final res = await _post('login.php', {
        'identity': identity,
        'password': password,
      });
      return res is Map<String, dynamic> ? res : {'success': false, 'error': 'Invalid response format'};
    } catch (e) {
      return {'success': false, 'error': e.toString()};
    }
  }

  // 3. Authentication: Register
  static Future<void> register(String username, String email, String password) async {
    await _post('register.php', {
      'username': username,
      'email': email,
      'password': password,
    });
  }

  // 4. Cart API Operations
  static Future<Map<String, dynamic>> getCart(int userId) async {
    final res = await _post('cart_api.php', {'user_id': userId, 'action': 'get'});
    return res is Map<String, dynamic> ? res : {'success': false, 'error': 'Invalid response format'};
  }

  static Future<Map<String, dynamic>> addToCart(int userId, int productId, int quantity) async {
    final res = await _post('cart_api.php', {
      'user_id': userId,
      'action': 'add',
      'product_id': productId,
      'quantity': quantity
    });
    return res is Map<String, dynamic> ? res : {'success': false, 'error': 'Invalid response format'};
  }

  static Future<Map<String, dynamic>> updateCartQuantity(int userId, int productId, int quantity) async {
    final res = await _post('cart_api.php', {
      'user_id': userId,
      'action': 'update',
      'product_id': productId,
      'quantity': quantity
    });
    return res is Map<String, dynamic> ? res : {'success': false, 'error': 'Invalid response format'};
  }

  static Future<Map<String, dynamic>> removeFromCart(int userId, int productId) async {
    final res = await _post('cart_api.php', {
      'user_id': userId,
      'action': 'remove',
      'product_id': productId
    });
    return res is Map<String, dynamic> ? res : {'success': false, 'error': 'Invalid response format'};
  }

  static Future<Map<String, dynamic>> clearCart(int userId) async {
    final res = await _post('cart_api.php', {'user_id': userId, 'action': 'clear'});
    return res is Map<String, dynamic> ? res : {'success': false, 'error': 'Invalid response format'};
  }

  // 5. Orders & Payments API Operations
  static Future<Map<String, dynamic>> checkout({
    required int userId,
    required String location,
    required String phone,
    required String paymentMethod,
    required List<Map<String, dynamic>> cartItems,
  }) async {
    try {
      final res = await _post('checkout.php', {
        'user_id': userId,
        'location': location,
        'phone': phone,
        'payment_method': paymentMethod,
        'cart_items': cartItems,
      });
      return res is Map<String, dynamic> ? res : {'success': false, 'error': 'Invalid response format'};
    } catch (e) {
      return {'success': false, 'error': e.toString()};
    }
  }

  static Future<Map<String, dynamic>> cancelOrder({
    required String orderId,
    required int userId,
  }) async {
    try {
      final res = await _post('cancel_order.php', {
        'order_id': orderId,
        'user_id': userId,
      });
      return res is Map<String, dynamic> ? res : {'success': false, 'error': 'Invalid response format'};
    } catch (e) {
      return {'success': false, 'error': e.toString()};
    }
  }

  // 5a. Fetch Add-ons
  static Future<List<dynamic>> fetchAddOns() async {
    try {
      final response = await http.get(Uri.parse('$baseUrl/add_ons_api.php'))
          .timeout(const Duration(seconds: 10));
      if (response.statusCode == 200) {
        final decoded = json.decode(response.body);
        if (decoded is Map<String, dynamic> && decoded['success'] == true) {
          return decoded['add_ons'] is List<dynamic> ? decoded['add_ons'] : [];
        } else {
          throw Exception(decoded is Map ? decoded['error'] ?? 'Failed to load add-ons' : 'Invalid format');
        }
      } else {
        throw Exception('Failed to load add-ons: ${response.statusCode}');
      }
    } catch (e) {
      throw Exception('Error fetching add-ons: $e');
    }
  }

  // 5b. Fetch Recommendations
  static Future<List<dynamic>> fetchRecommendations(int userId, {String? filterType}) async {
    try {
      final Map<String, dynamic> body = {'user_id': userId};
      if (filterType != null) {
        body['filter_type'] = filterType;
      }
      final res = await _post('recommendations_api.php', body);
      if (res is Map<String, dynamic> && res['success'] == true) {
        return res['recommendations'] is List<dynamic> ? res['recommendations'] : [];
      } else {
        throw Exception(res is Map ? res['error'] ?? 'Failed to load recommendations' : 'Invalid format');
      }
    } catch (e) {
      throw Exception('Error fetching recommendations: $e');
    }
  }

  static Future<Map<String, dynamic>> fetchOrders(int userId) async {
    final res = await _post('orders_api.php', {'user_id': userId, 'action': 'get'});
    return res is Map<String, dynamic> ? res : {'success': false, 'error': 'Invalid response format'};
  }

  // 6. Saved Addresses API Operations
  static Future<Map<String, dynamic>> fetchAddresses(int userId) async {
    final res = await _post('addresses_api.php', {'user_id': userId, 'action': 'get'});
    return res is Map<String, dynamic> ? res : {'success': false, 'error': 'Invalid response format'};
  }

  static Future<Map<String, dynamic>> addAddress({
    required int userId,
    required String type,
    required String streetAddress,
    required String aptNumber,
    required String floor,
    required String phoneNumber,
  }) async {
    final res = await _post('addresses_api.php', {
      'user_id': userId,
      'action': 'add',
      'type': type,
      'streetAddress': streetAddress,
      'aptNumber': aptNumber,
      'floor': floor,
      'phoneNumber': phoneNumber
    });
    return res is Map<String, dynamic> ? res : {'success': false, 'error': 'Invalid response format'};
  }

  static Future<Map<String, dynamic>> updateAddress({
    required int userId,
    required int addressId,
    required String type,
    required String streetAddress,
    required String aptNumber,
    required String floor,
    required String phoneNumber,
  }) async {
    final res = await _post('addresses_api.php', {
      'user_id': userId,
      'action': 'update',
      'address_id': addressId,
      'type': type,
      'streetAddress': streetAddress,
      'aptNumber': aptNumber,
      'floor': floor,
      'phoneNumber': phoneNumber
    });
    return res is Map<String, dynamic> ? res : {'success': false, 'error': 'Invalid response format'};
  }

  static Future<Map<String, dynamic>> deleteAddress(int userId, int addressId) async {
    final res = await _post('addresses_api.php', {
      'user_id': userId,
      'action': 'delete',
      'address_id': addressId
    });
    return res is Map<String, dynamic> ? res : {'success': false, 'error': 'Invalid response format'};
  }

  static Future<Map<String, dynamic>> selectAddress(int userId, int addressId) async {
    final res = await _post('addresses_api.php', {
      'user_id': userId,
      'action': 'select',
      'address_id': addressId
    });
    return res is Map<String, dynamic> ? res : {'success': false, 'error': 'Invalid response format'};
  }

  // 7. Profile API Operations
  static Future<Map<String, dynamic>> fetchProfile(int userId) async {
    final res = await _post('profile_api.php', {'user_id': userId, 'action': 'get'});
    return res is Map<String, dynamic> ? res : {'success': false, 'error': 'Invalid response format'};
  }

  static Future<Map<String, dynamic>> updateProfile({
    required int userId,
    required String username,
    required String phone,
    required int age,
    required double weight,
    required double height,
    required String gender,
    required String fitnessGoal,
    required String activityLevel,
    Uint8List? avatarBytes,
    String? avatarFileName,
  }) async {
    try {
      final uri = Uri.parse('$baseUrl/profile_api.php');
      final request = http.MultipartRequest('POST', uri);

      request.fields['user_id'] = userId.toString();
      request.fields['action'] = 'update';
      request.fields['username'] = username;
      request.fields['phone'] = phone;
      request.fields['age'] = age.toString();
      request.fields['weight'] = weight.toString();
      request.fields['height'] = height.toString();
      request.fields['gender'] = gender;
      request.fields['fitness_goal'] = fitnessGoal;
      request.fields['activity_level'] = activityLevel;

      if (avatarBytes != null && avatarFileName != null) {
        request.files.add(http.MultipartFile.fromBytes(
          'avatar',
          avatarBytes,
          filename: avatarFileName,
        ));
      }

      final streamedResponse = await request.send().timeout(const Duration(seconds: 15));
      final response = await http.Response.fromStream(streamedResponse);
      final res = _handleResponse(response);
      return res is Map<String, dynamic> ? res : {'success': false, 'error': 'Invalid response format'};
    } catch (e) {
      throw Exception('Failed to update profile: $e');
    }
  }

  static Future<Map<String, dynamic>> deleteAccount(int userId) async {
    final res = await _post('profile_api.php', {'user_id': userId, 'action': 'delete'});
    return res is Map<String, dynamic> ? res : {'success': false, 'error': 'Invalid response format'};
  }

  // 8. Settings API Operations
  static Future<Map<String, dynamic>> changeEmail(int userId, String newEmail) async {
    final res = await _post('settings_api.php', {
      'user_id': userId,
      'action': 'change_email',
      'new_email': newEmail
    });
    return res is Map<String, dynamic> ? res : {'success': false, 'error': 'Invalid response format'};
  }

  static Future<Map<String, dynamic>> changePassword({
    required int userId,
    required String currentPassword,
    required String newPassword,
  }) async {
    final res = await _post('settings_api.php', {
      'user_id': userId,
      'action': 'change_password',
      'current_password': currentPassword,
      'new_password': newPassword
    });
    return res is Map<String, dynamic> ? res : {'success': false, 'error': 'Invalid response format'};
  }
}