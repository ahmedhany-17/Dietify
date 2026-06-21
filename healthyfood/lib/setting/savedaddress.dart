import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:healthyfood/providers/app_state.dart';
import 'package:healthyfood/models/address.dart';

class SavedAddress extends StatelessWidget {
  const SavedAddress({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text("Saved Addresses"),
        backgroundColor: Color(0xfffc7e1a),
        foregroundColor: Colors.white,
      ),
      body: Padding(
        padding: const EdgeInsets.all(20),
        child: Consumer<AppState>(
          builder: (context, appState, child) {
            return ListView(
              children: [
                const Icon(
                  Icons.location_on,
                  size: 80,
                  color: Color(0xfffc7e1a),
                ),
                const SizedBox(height: 20),
                const Center(
                  child: Text(
                    "Your Saved Addresses",
                    style: TextStyle(
                      fontSize: 22,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                ),
                const SizedBox(height: 30),
                if (appState.addresses.isEmpty)
                  const Center(
                    child: Padding(
                      padding: EdgeInsets.all(20),
                      child: Text("No saved addresses.", style: TextStyle(color: Colors.grey)),
                    ),
                  )
                else
                  ...appState.addresses.map((address) {
                    return GestureDetector(
                      onTap: () => appState.selectAddress(address.id),
                      child: Container(
                        margin: const EdgeInsets.only(bottom: 20),
                        padding: const EdgeInsets.all(16),
                        decoration: BoxDecoration(
                          color: address.isSelected ? const Color(0xFFFFF4EB) : Colors.white,
                          borderRadius: BorderRadius.circular(14),
                          border: Border.all(
                            color: address.isSelected ? const Color(0xfffc7e1a) : Colors.transparent,
                            width: 2,
                          ),
                          boxShadow: [
                            BoxShadow(
                              color: Colors.grey.shade300,
                              blurRadius: 8,
                              offset: const Offset(0, 4),
                            ),
                          ],
                        ),
                        child: Row(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Radio<String>(
                              value: address.id,
                              groupValue: appState.selectedAddress?.id,
                              activeColor: const Color(0xfffc7e1a),
                              onChanged: (val) {
                                if (val != null) appState.selectAddress(val);
                              },
                            ),
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(
                                    address.type,
                                    style: const TextStyle(
                                      fontSize: 16,
                                      fontWeight: FontWeight.bold,
                                    ),
                                  ),
                                  const SizedBox(height: 8),
                                  Text(
                                    address.formattedAddress,
                                    style: const TextStyle(
                                      fontSize: 15,
                                      color: Colors.black54,
                                      height: 1.4,
                                    ),
                                  ),
                                  const SizedBox(height: 4),
                                  Text(
                                    "Phone: ${address.phoneNumber}",
                                    style: const TextStyle(
                                      fontSize: 14,
                                      color: Colors.black45,
                                    ),
                                  ),
                                  const SizedBox(height: 12),
                                  Row(
                                    children: [
                                      TextButton(
                                        onPressed: () {
                                          _showEditAddressDialog(context, appState, address);
                                        },
                                        child: const Text("Edit"),
                                      ),
                                      TextButton(
                                        onPressed: () => appState.deleteAddress(address.id),
                                        child: const Text(
                                          "Delete",
                                          style: TextStyle(color: Color(0xfffc7e1a)),
                                        ),
                                      ),
                                    ],
                                  ),
                                ],
                              ),
                            ),
                          ],
                        ),
                      ),
                    );
                  }),
                const SizedBox(height: 10),
                Container(
                  height: 50,
                  decoration: BoxDecoration(
                    color: const Color(0xfffc7e1a),
                    borderRadius: BorderRadius.circular(14),
                  ),
                  child: TextButton(
                    onPressed: () {
                      _showAddAddressDialog(context, appState);
                    },
                    child: const Center(
                      child: Text(
                        "Add New Address",
                        style: TextStyle(
                          color: Colors.white,
                          fontSize: 16,
                        ),
                      ),
                    ),
                  ),
                ),
              ],
            );
          }
        ),
      ),
    );
  }

  void _showAddAddressDialog(BuildContext context, AppState appState) {
    final typeController = TextEditingController();
    final streetController = TextEditingController();
    final aptController = TextEditingController();
    final floorController = TextEditingController();
    final phoneController = TextEditingController();

    showDialog(
      context: context,
      builder: (context) {
        return AlertDialog(
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
          title: const Text("Add New Address"),
          content: SingleChildScrollView(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                TextField(
                  controller: typeController,
                  decoration: const InputDecoration(
                    labelText: "Type (e.g. Home, Work)",
                    border: OutlineInputBorder(),
                  ),
                ),
                const SizedBox(height: 15),
                TextField(
                  controller: streetController,
                  decoration: const InputDecoration(
                    labelText: "Street Address",
                    border: OutlineInputBorder(),
                  ),
                ),
                const SizedBox(height: 15),
                Row(
                  children: [
                    Expanded(
                      child: TextField(
                        controller: aptController,
                        decoration: const InputDecoration(
                          labelText: "Apt No.",
                          border: OutlineInputBorder(),
                        ),
                      ),
                    ),
                    const SizedBox(width: 15),
                    Expanded(
                      child: TextField(
                        controller: floorController,
                        decoration: const InputDecoration(
                          labelText: "Floor",
                          border: OutlineInputBorder(),
                        ),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 15),
                TextField(
                  controller: phoneController,
                  keyboardType: TextInputType.phone,
                  decoration: const InputDecoration(
                    labelText: "Phone Number",
                    border: OutlineInputBorder(),
                  ),
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
                if (typeController.text.isNotEmpty && streetController.text.isNotEmpty) {
                  appState.addAddress(
                    typeController.text,
                    streetController.text,
                    aptController.text,
                    floorController.text,
                    phoneController.text,
                  );
                  Navigator.pop(context);
                }
              },
              child: const Text("Save", style: TextStyle(color: Colors.white)),
            ),
          ],
        );
      },
    );
  }

  void _showEditAddressDialog(BuildContext context, AppState appState, Address address) {
    final typeController = TextEditingController(text: address.type);
    final streetController = TextEditingController(text: address.streetAddress);
    final aptController = TextEditingController(text: address.aptNumber);
    final floorController = TextEditingController(text: address.floor);
    final phoneController = TextEditingController(text: address.phoneNumber);

    showDialog(
      context: context,
      builder: (context) {
        return AlertDialog(
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
          title: const Text("Edit Address"),
          content: SingleChildScrollView(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                TextField(
                  controller: typeController,
                  decoration: const InputDecoration(
                    labelText: "Type (e.g. Home, Work)",
                    border: OutlineInputBorder(),
                  ),
                ),
                const SizedBox(height: 15),
                TextField(
                  controller: streetController,
                  decoration: const InputDecoration(
                    labelText: "Street Address",
                    border: OutlineInputBorder(),
                  ),
                ),
                const SizedBox(height: 15),
                Row(
                  children: [
                    Expanded(
                      child: TextField(
                        controller: aptController,
                        decoration: const InputDecoration(
                          labelText: "Apt No.",
                          border: OutlineInputBorder(),
                        ),
                      ),
                    ),
                    const SizedBox(width: 15),
                    Expanded(
                      child: TextField(
                        controller: floorController,
                        decoration: const InputDecoration(
                          labelText: "Floor",
                          border: OutlineInputBorder(),
                        ),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 15),
                TextField(
                  controller: phoneController,
                  keyboardType: TextInputType.phone,
                  decoration: const InputDecoration(
                    labelText: "Phone Number",
                    border: OutlineInputBorder(),
                  ),
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
                if (typeController.text.isNotEmpty && streetController.text.isNotEmpty) {
                  appState.updateAddress(
                    address.id,
                    typeController.text,
                    streetController.text,
                    aptController.text,
                    floorController.text,
                    phoneController.text,
                  );
                  Navigator.pop(context);
                }
              },
              child: const Text("Update", style: TextStyle(color: Colors.white)),
            ),
          ],
        );
      },
    );
  }
}
