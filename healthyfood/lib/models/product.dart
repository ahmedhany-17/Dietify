class Product {
  final String id;
  final String name;
  final int calories;
  final String type;
  final String image;
  final double price;
  final String description;
  final double protein;
  final double fats;
  final double carbs;
  List<Product> addOns = [];

  Product({
    required this.id,
    required this.name,
    required this.calories,
    required this.type,
    required this.image,
    required this.price,
    this.description = "A healthy and delicious choice for your diet.",
    double protein = 0.0,
    double fats = 0.0,
    double carbs = 0.0,
  })  : protein = protein != 0.0 ? protein : _calculateProtein(calories, name, type),
        fats = fats != 0.0 ? fats : _calculateFats(calories, name, type),
        carbs = carbs != 0.0 ? carbs : _calculateCarbs(calories, name, type);

  static double _calculateProtein(int cal, String name, String type) {
    if (cal <= 0) return 0.0;
    final n = name.toLowerCase();
    final t = type.toLowerCase();
    if (n.contains('chicken') || n.contains('meat') || n.contains('egg') || n.contains('salmon') || n.contains('fish') || t.contains('protein')) {
      return (cal * 0.4) / 4;
    }
    if (n.contains('salad') || n.contains('broccoli') || n.contains('spinach') || n.contains('avocado') || t.contains('fat') || t.contains('vegetable')) {
      return (cal * 0.15) / 4;
    }
    if (n.contains('rice') || n.contains('oats') || n.contains('bread') || n.contains('toast') || t.contains('carbs') || t.contains('grain')) {
      return (cal * 0.15) / 4;
    }
    return (cal * 0.3) / 4;
  }

  static double _calculateFats(int cal, String name, String type) {
    if (cal <= 0) return 0.0;
    final n = name.toLowerCase();
    final t = type.toLowerCase();
    if (n.contains('chicken') || n.contains('meat') || n.contains('egg') || n.contains('salmon') || n.contains('fish') || t.contains('protein')) {
      return (cal * 0.3) / 9;
    }
    if (n.contains('salad') || n.contains('broccoli') || n.contains('spinach') || n.contains('avocado') || t.contains('fat') || t.contains('vegetable')) {
      return (cal * 0.6) / 9;
    }
    if (n.contains('rice') || n.contains('oats') || n.contains('bread') || n.contains('toast') || t.contains('carbs') || t.contains('grain')) {
      return (cal * 0.15) / 9;
    }
    return (cal * 0.3) / 9;
  }

  static double _calculateCarbs(int cal, String name, String type) {
    if (cal <= 0) return 0.0;
    final n = name.toLowerCase();
    final t = type.toLowerCase();
    if (n.contains('chicken') || n.contains('meat') || n.contains('egg') || n.contains('salmon') || n.contains('fish') || t.contains('protein')) {
      return (cal * 0.3) / 4;
    }
    if (n.contains('salad') || n.contains('broccoli') || n.contains('spinach') || n.contains('avocado') || t.contains('fat') || t.contains('vegetable')) {
      return (cal * 0.25) / 4;
    }
    if (n.contains('rice') || n.contains('oats') || n.contains('bread') || n.contains('toast') || t.contains('carbs') || t.contains('grain')) {
      return (cal * 0.7) / 4;
    }
    return (cal * 0.4) / 4;
  }

  factory Product.fromJson(Map<String, dynamic> json) {
    int parseToInt(dynamic val) {
      if (val == null) return 0;
      if (val is num) return val.toInt();
      if (val is String) return int.tryParse(val) ?? double.tryParse(val)?.toInt() ?? 0;
      return 0;
    }

    double parseToDouble(dynamic val) {
      if (val == null) return 0.0;
      if (val is num) return val.toDouble();
      if (val is String) return double.tryParse(val) ?? 0.0;
      return 0.0;
    }

    return Product(
      id: json['id']?.toString() ?? '',
      name: json['name'] ?? '',
      calories: parseToInt(json['calories']),
      type: json['type'] ?? '',
      image: json['image'] ?? json['image_url'] ?? json['image_path'] ?? '',
      price: parseToDouble(json['price']),
      description: json['description'] ?? "A healthy and delicious choice for your diet.",
      protein: parseToDouble(json['protein']),
      fats: parseToDouble(json['fats'] ?? json['fat']),
      carbs: parseToDouble(json['carbs']),
    );
  }
}
