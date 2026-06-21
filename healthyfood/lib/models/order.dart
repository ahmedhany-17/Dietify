import 'package:healthyfood/models/cart_item.dart';

class OrderModel {
  final String id;
  final List<CartItem> items;
  final double total;
  final DateTime date;
  final String status;

  OrderModel({
    required this.id,
    required this.items,
    required this.total,
    required this.date,
    this.status = "Completed",
  });
}
