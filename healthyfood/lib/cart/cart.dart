import 'package:flutter/material.dart';
import 'package:healthyfood/cart/successful.dart';
import 'package:provider/provider.dart';
import 'package:healthyfood/providers/app_state.dart';
import 'package:healthyfood/models/cart_item.dart';
import 'package:healthyfood/setting/savedaddress.dart';
import 'package:healthyfood/models/credit_card.dart';

class MyCartScreen extends StatelessWidget {
  const MyCartScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.white,
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0,

        centerTitle: true,
        title: Consumer<AppState>(
          builder: (context, appState, child) => Column(
            children: [
              const Text(
                "My Cart",
                style: TextStyle(
                  color: Colors.black,
                  fontWeight: FontWeight.bold,
                  fontSize: 18,
                ),
              ),
              Text(
                "${appState.cartItemCount} items • ${appState.cartTotalCalories} kcal",
                style: const TextStyle(color: Colors.grey, fontSize: 12),
              ),
            ],
          ),
        ),
      ),
      body: SingleChildScrollView(
        child: Padding(
          padding: const EdgeInsets.all(16.0),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: const Color(0xFFE8F5E9),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Consumer<AppState>(
                  builder: (context, appState, child) {
                    double progress = appState.dailyCalorieGoal > 0 
                      ? (appState.cartTotalCalories / appState.dailyCalorieGoal) * 100 
                      : 0;
                    return Row(
                      children: [
                        const Icon(Icons.eco, color: Colors.green),
                        const SizedBox(width: 10),
                        Expanded(
                          child: Text(
                            "You've hit ${progress.toStringAsFixed(0)}% of your daily calorie goal with this order.",
                            style: const TextStyle(
                              color: Colors.green,
                              fontSize: 13,
                              fontWeight: FontWeight.w500,
                            ),
                          ),
                        ),
                      ],
                    );
                  }
                ),
              ),
              const SizedBox(height: 25),
              const Text(
                "Your Order",
                style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold),
              ),
              const SizedBox(height: 15),
              Consumer<AppState>(
                builder: (context, appState, child) {
                  if (appState.cartItems.isEmpty) {
                    return const Padding(
                      padding: EdgeInsets.symmetric(vertical: 40),
                      child: Center(
                        child: Text("Your cart is empty.", style: TextStyle(color: Colors.grey, fontSize: 16)),
                      ),
                    );
                  }
                  return Column(
                    children: appState.cartItems.map((item) => _buildCartItem(context, item, appState)).toList(),
                  );
                },
              ),
              Consumer<AppState>(
                builder: (context, appState, child) {
                  if (appState.addOns.isEmpty) {
                    return const SizedBox();
                  }
                  return Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text(
                        "Healthy Add-ons",
                        style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                      ),
                      const SizedBox(height: 15),
                      SingleChildScrollView(
                        scrollDirection: Axis.horizontal,
                        child: Row(
                          children: appState.addOns.map((product) {
                            return GestureDetector(
                              onTap: () {
                                appState.addToCart(product);
                                ScaffoldMessenger.of(context).showSnackBar(
                                  SnackBar(
                                    content: Text('Added ${product.name} to cart'),
                                    duration: const Duration(seconds: 1),
                                  ),
                                );
                              },
                              child: Container(
                                width: 140,
                                margin: const EdgeInsets.only(right: 15),
                                padding: const EdgeInsets.all(10),
                                decoration: BoxDecoration(
                                  border: Border.all(color: Colors.grey.shade200),
                                  borderRadius: BorderRadius.circular(15),
                                ),
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Container(
                                      height: 80,
                                      width: double.infinity,
                                      decoration: BoxDecoration(
                                        color: Colors.grey.shade100,
                                        borderRadius: BorderRadius.circular(10),
                                        image: DecorationImage(
                                          image: NetworkImage(product.image),
                                          fit: BoxFit.cover,
                                        ),
                                      ),
                                    ),
                                    const SizedBox(height: 8),
                                    Text(
                                      product.name,
                                      style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13),
                                      maxLines: 1,
                                      overflow: TextOverflow.ellipsis,
                                    ),
                                    Text(
                                      "${product.price.toStringAsFixed(0)} EGP • ${product.calories} kcal",
                                      style: const TextStyle(color: Colors.grey, fontSize: 11),
                                    ),
                                  ],
                                ),
                              ),
                            );
                          }).toList(),
                        ),
                      ),
                      const SizedBox(height: 30),
                    ],
                  );
                },
              ),
              Consumer<AppState>(
                builder: (context, appState, child) {
                  final address = appState.selectedAddress;
                  if (address == null) return const SizedBox();
                  return Container(
                    margin: const EdgeInsets.only(bottom: 15),
                    padding: const EdgeInsets.all(15),
                    decoration: BoxDecoration(
                      border: Border.all(color: Colors.grey.shade200),
                      borderRadius: BorderRadius.circular(15),
                    ),
                    child: Row(
                      children: [
                        const Icon(Icons.location_on, color: Color(0xfffc7e1a)),
                        const SizedBox(width: 15),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text("Deliver to ${address.type}", style: const TextStyle(fontWeight: FontWeight.bold)),
                              Text(address.formattedAddress, style: const TextStyle(color: Colors.grey, fontSize: 12), maxLines: 1, overflow: TextOverflow.ellipsis),
                              Text("Phone: ${address.phoneNumber}", style: const TextStyle(color: Colors.grey, fontSize: 12)),
                            ],
                          ),
                        ),
                        const SizedBox(width: 10),
                        GestureDetector(
                          onTap: () {
                            Navigator.push(context, MaterialPageRoute(builder: (_) => const SavedAddress()));
                          },
                          child: const Text("Change", style: TextStyle(color: Colors.green, fontWeight: FontWeight.bold)),
                        ),
                      ],
                    ),
                  );
                }
              ),
              Consumer<AppState>(
                builder: (context, appState, child) {
                  final isCash = appState.selectedPaymentMethodId == 'CASH';
                  final card = appState.selectedCreditCard;
                  return Container(
                    padding: const EdgeInsets.all(15),
                    decoration: BoxDecoration(
                      border: Border.all(color: Colors.grey.shade200),
                      borderRadius: BorderRadius.circular(15),
                    ),
                    child: Row(
                      children: [
                        Icon(
                          isCash ? Icons.money : Icons.credit_card, 
                          color: isCash ? Colors.green : Colors.black54,
                        ),
                        const SizedBox(width: 15),
                        Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(isCash ? "Cash on Delivery" : "Mastercard **** ${card?.last4 ?? ''}", style: const TextStyle(fontWeight: FontWeight.bold)),
                            if (!isCash && card != null)
                              Text("Expires ${card.expiryDate}", style: const TextStyle(color: Colors.grey, fontSize: 12)),
                          ],
                        ),
                        const Spacer(),
                        GestureDetector(
                          onTap: () => _showPaymentBottomSheet(context, appState),
                          child: const Text("Change", style: TextStyle(color: Colors.green, fontWeight: FontWeight.bold)),
                        ),
                      ],
                    ),
                  );
                }
              ),
              const SizedBox(height: 25),
              Consumer<AppState>(
                builder: (context, appState, child) => Column(
                  children: [
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [const Text("Subtotal"), Text("${appState.cartSubtotal.toStringAsFixed(0)} EGP")],
                    ),
                    const SizedBox(height: 10),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [const Text("Delivery"), Text("${appState.cartDelivery.toStringAsFixed(0)} EGP")],
                    ),
                    const Padding(padding: EdgeInsets.symmetric(vertical: 10), child: Divider()),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        const Text("Grand Total", style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                        Text("${appState.cartTotal.toStringAsFixed(0)} EGP", style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                      ],
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 30),
              Consumer<AppState>(
                builder: (context, appState, child) {
                  return GestureDetector(
                    onTap: () async {
                      if (appState.cartItems.isEmpty) return;
                      // Show loading dialog
                      showDialog(
                        context: context,
                        barrierDismissible: false,
                        builder: (context) => const Center(child: CircularProgressIndicator(color: Color(0xfffc7e1a))),
                      );
                      
                      try {
                        final orderId = await appState.checkout();
                        if (context.mounted) {
                          Navigator.pop(context); // remove loading
                          Navigator.pushReplacement(context, MaterialPageRoute(builder: (context) => SuccessfulPage(orderId: orderId)));
                        }
                      } catch (e) {
                        if (context.mounted) {
                          Navigator.pop(context); // remove loading
                          ScaffoldMessenger.of(context).showSnackBar(
                            SnackBar(
                              content: Text('Checkout failed: ${e.toString()}'),
                              backgroundColor: Colors.red,
                              behavior: SnackBarBehavior.floating,
                            ),
                          );
                        }
                      }
                    },
                    child: Container(
                      width: double.infinity,
                      padding: const EdgeInsets.symmetric(vertical: 18),
                      decoration: BoxDecoration(
                        color: appState.cartItems.isEmpty ? Colors.grey : const Color(0xfffc7e1a),
                        borderRadius: BorderRadius.circular(15),
                      ),
                      child: Center(
                        child: Text(
                          "Checkout ${appState.cartTotal.toStringAsFixed(0)} EGP",
                          style: const TextStyle(color: Colors.white, fontSize: 18, fontWeight: FontWeight.bold),
                        ),
                      ),
                    ),
                  );
                }
              ),
              const SizedBox(height: 20),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildCartItem(BuildContext context, CartItem item, AppState appState) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 20),
      child: Row(
        children: [
          Container(
            width: 80,
            height: 80,
            decoration: BoxDecoration(
              color: Colors.grey.shade200,
              borderRadius: BorderRadius.circular(15),
              image: DecorationImage(
                image: NetworkImage(item.product.image),
                fit: BoxFit.cover,
              ),
            ),
          ),
          const SizedBox(width: 15),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  item.product.name,
                  style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
                const SizedBox(height: 4),
                Text(
                  "${item.product.price} EGP",
                  style: const TextStyle(color: Color(0xfffc7e1a), fontWeight: FontWeight.bold),
                ),
                const SizedBox(height: 4),
                Text(
                  "${item.product.calories} kcal",
                  style: const TextStyle(color: Colors.grey, fontSize: 12),
                ),
              ],
            ),
          ),
          Row(
            children: [
              GestureDetector(
                onTap: () {
                  appState.updateQuantity(item.product, item.quantity - 1);
                },
                child: Container(
                  padding: const EdgeInsets.all(4),
                  decoration: BoxDecoration(
                    color: Colors.grey.shade200,
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: const Icon(Icons.remove, size: 16),
                ),
              ),
              const SizedBox(width: 12),
              Text(
                "${item.quantity}",
                style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
              ),
              const SizedBox(width: 12),
              GestureDetector(
                onTap: () {
                  appState.updateQuantity(item.product, item.quantity + 1);
                },
                child: Container(
                  padding: const EdgeInsets.all(4),
                  decoration: BoxDecoration(
                    color: const Color(0xfffc7e1a),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: const Icon(Icons.add, color: Colors.white, size: 16),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }


  void _showPaymentBottomSheet(BuildContext context, AppState appState) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(25))),
      builder: (context) {
        return Container(
          padding: const EdgeInsets.all(20),
          height: MediaQuery.of(context).size.height * 0.6,
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              const Text("Payment Methods", style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold)),
              const SizedBox(height: 20),
              Expanded(
                child: ListView(
                  children: [
                    ...appState.creditCards.map((card) => ListTile(
                      leading: const Icon(Icons.credit_card, color: Colors.blue),
                      title: Text("Mastercard **** ${card.last4}"),
                      subtitle: Text("Expires ${card.expiryDate}"),
                      trailing: Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          IconButton(
                            icon: const Icon(Icons.edit, color: Colors.grey, size: 20),
                            onPressed: () {
                              Navigator.pop(context);
                              _showAddEditCardDialog(context, appState, card: card);
                            },
                          ),
                          IconButton(
                            icon: const Icon(Icons.delete_outline_rounded, color: Colors.redAccent, size: 20),
                            onPressed: () {
                              appState.deleteCreditCard(card.id);
                              ScaffoldMessenger.of(context).showSnackBar(
                                const SnackBar(
                                  content: Text("Card removed successfully!"),
                                  behavior: SnackBarBehavior.floating,
                                  duration: Duration(seconds: 1),
                                ),
                              );
                            },
                          ),
                          if (appState.selectedPaymentMethodId == card.id)
                            const Icon(Icons.check_circle, color: Colors.green),
                        ],
                      ),
                      onTap: () {
                        appState.selectPaymentMethod(card.id);
                        Navigator.pop(context);
                      },
                    )),
                    ListTile(
                      leading: const Icon(Icons.money, color: Colors.green),
                      title: const Text("Cash on Delivery"),
                      trailing: appState.selectedPaymentMethodId == 'CASH' ? const Icon(Icons.check_circle, color: Colors.green) : null,
                      onTap: () {
                        appState.selectPaymentMethod("CASH");
                        Navigator.pop(context);
                      },
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 15),
              SizedBox(
                width: double.infinity,
                child: ElevatedButton.icon(
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xfffc7e1a),
                    padding: const EdgeInsets.symmetric(vertical: 15),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(15)),
                  ),
                  icon: const Icon(Icons.add, color: Colors.white),
                  label: const Text("Add New Card", style: TextStyle(color: Colors.white, fontSize: 16)),
                  onPressed: () {
                    Navigator.pop(context);
                    _showAddEditCardDialog(context, appState);
                  },
                ),
              ),
            ],
          ),
        );
      },
    );
  }

  void _showAddEditCardDialog(BuildContext context, AppState appState, {CreditCard? card}) {
    final isEditing = card != null;
    final numController = TextEditingController(text: card?.cardNumber);
    final nameController = TextEditingController(text: card?.cardHolderName);
    final expController = TextEditingController(text: card?.expiryDate);
    final cvvController = TextEditingController(text: card?.cvv);

    showDialog(
      context: context,
      builder: (context) {
        return AlertDialog(
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
          title: Text(isEditing ? "Edit Card" : "Add New Card"),
          content: SingleChildScrollView(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                TextField(
                  controller: numController,
                  keyboardType: TextInputType.number,
                  decoration: const InputDecoration(labelText: "Card Number", border: OutlineInputBorder()),
                ),
                const SizedBox(height: 15),
                TextField(
                  controller: nameController,
                  decoration: const InputDecoration(labelText: "Cardholder Name", border: OutlineInputBorder()),
                ),
                const SizedBox(height: 15),
                Row(
                  children: [
                    Expanded(
                      child: TextField(
                        controller: expController,
                        decoration: const InputDecoration(labelText: "Expiry (MM/YY)", border: OutlineInputBorder()),
                      ),
                    ),
                    const SizedBox(width: 15),
                    Expanded(
                      child: TextField(
                        controller: cvvController,
                        keyboardType: TextInputType.number,
                        decoration: const InputDecoration(labelText: "CVV", border: OutlineInputBorder()),
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(context),
              child: const Text("Cancel", style: TextStyle(color: Colors.grey)),
            ),
            ElevatedButton(
              style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xfffc7e1a),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
              ),
              onPressed: () {
                if (numController.text.isNotEmpty && expController.text.isNotEmpty) {
                  if (isEditing) {
                    appState.updateCreditCard(card.id, numController.text, nameController.text, expController.text, cvvController.text);
                  } else {
                    appState.addCreditCard(numController.text, nameController.text, expController.text, cvvController.text);
                  }
                  Navigator.pop(context);
                }
              },
              child: Text(isEditing ? "Update" : "Save", style: const TextStyle(color: Colors.white)),
            ),
          ],
        );
      },
    );
  }
}