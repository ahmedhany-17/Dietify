import 'package:flutter/material.dart';
import 'package:healthyfood/database/database.dart';
import 'package:provider/provider.dart';
import 'dart:typed_data';
import 'package:image_picker/image_picker.dart';
import 'package:healthyfood/providers/app_state.dart';
import 'package:healthyfood/setting/favorites.dart';
import 'package:healthyfood/auth/auth.dart';
import 'package:healthyfood/setting/privacy.dart';

import '../ai chatbot/chatbot.dart';
import '../home/home.dart';
import '../setting/setting.dart';

class ProfilePage extends StatelessWidget {
  const ProfilePage({super.key});

  void _showEditProfileModal(BuildContext context, AppState appState) {
    final nameController = TextEditingController(text: appState.userName);
    final ageController = TextEditingController(text: appState.userAge.toString());
    final heightController = TextEditingController(text: appState.userHeight.toStringAsFixed(0));
    final weightController = TextEditingController(text: appState.userWeight.toStringAsFixed(0));
    final phoneController = TextEditingController(text: appState.userPhone);
    String selectedGender = appState.userGender.isEmpty ? 'Male' : appState.userGender;
    Uint8List? pickedImageBytes;
    String? pickedImageName;
    bool isSaving = false;

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (context) {
        return StatefulBuilder(
          builder: (context, setModalState) {
            return Container(
              padding: EdgeInsets.only(
                left: 25,
                right: 25,
                top: 25,
                bottom: MediaQuery.of(context).viewInsets.bottom + 25,
              ),
              decoration: const BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.vertical(top: Radius.circular(30)),
              ),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Center(
                    child: Container(
                      width: 50,
                      height: 5,
                      decoration: BoxDecoration(color: Colors.grey.shade300, borderRadius: BorderRadius.circular(10)),
                    ),
                  ),
                  const SizedBox(height: 25),
                  const Text("Edit Profile", style: TextStyle(fontSize: 22, fontWeight: FontWeight.bold)),
                  const SizedBox(height: 20),
                  Center(
                    child: Stack(
                      alignment: Alignment.bottomRight,
                      children: [
                        CircleAvatar(
                          radius: 45,
                          backgroundColor: Colors.grey.shade200,
                          backgroundImage: pickedImageBytes != null
                              ? MemoryImage(pickedImageBytes!) as ImageProvider
                              : (appState.userAvatar.isEmpty || appState.userAvatar == AppState.defaultAvatarUrl)
                                  ? const AssetImage("assets/images/logo.png") as ImageProvider
                                  : NetworkImage(appState.userAvatar) as ImageProvider,
                        ),
                        GestureDetector(
                          onTap: () async {
                            final ImagePicker picker = ImagePicker();
                            final XFile? image = await picker.pickImage(source: ImageSource.gallery);
                            if (image != null) {
                              final bytes = await image.readAsBytes();
                              setModalState(() {
                                pickedImageBytes = bytes;
                                pickedImageName = image.name;
                              });
                            }
                          },
                          child: Container(
                            padding: const EdgeInsets.all(6),
                            decoration: const BoxDecoration(color: Color(0xfffc7e1a), shape: BoxShape.circle),
                            child: const Icon(Icons.camera_alt_outlined, color: Colors.white, size: 16),
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 20),
                  _buildTextField("Display Name", nameController, Icons.person_outline),
                  const SizedBox(height: 15),
                  Row(
                    children: [
                      Expanded(child: _buildTextField("Age", ageController, Icons.calendar_today_outlined, isNumber: true)),
                      const SizedBox(width: 15),
                      Expanded(child: _buildTextField("Height (cm)", heightController, Icons.height, isNumber: true)),
                    ],
                  ),
                  const SizedBox(height: 15),
                  Row(
                    children: [
                      Expanded(child: _buildTextField("Weight (kg)", weightController, Icons.monitor_weight_outlined, isNumber: true)),
                      const SizedBox(width: 15),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Text("Gender", style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Colors.grey)),
                            const SizedBox(height: 8),
                            DropdownButtonFormField<String>(
                              initialValue: ['Male', 'Female'].contains(selectedGender) ? selectedGender : 'Male',
                              items: ['Male', 'Female']
                                  .map((g) => DropdownMenuItem(value: g, child: Text(g, style: const TextStyle(fontSize: 14))))
                                  .toList(),
                              onChanged: (val) {
                                setModalState(() {
                                  selectedGender = val ?? 'Male';
                                });
                              },
                              decoration: InputDecoration(
                                prefixIcon: const Icon(Icons.transgender_outlined, color: Color(0xfffc7e1a), size: 20),
                                filled: true,
                                fillColor: Colors.grey.shade100,
                                border: OutlineInputBorder(borderRadius: BorderRadius.circular(15), borderSide: BorderSide.none),
                                contentPadding: const EdgeInsets.symmetric(vertical: 4, horizontal: 10),
                              ),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 15),
                  _buildTextField("Phone Number", phoneController, Icons.phone_outlined, isNumber: true),
                  const SizedBox(height: 30),
                  SizedBox(
                    width: double.infinity,
                    child: ElevatedButton(
                      onPressed: isSaving
                          ? null
                          : () async {
                              setModalState(() {
                                isSaving = true;
                              });
                              try {
                                await appState.updateProfile(
                                  name: nameController.text,
                                  age: int.tryParse(ageController.text),
                                  height: double.tryParse(heightController.text),
                                  weight: double.tryParse(weightController.text),
                                  phone: phoneController.text,
                                  gender: selectedGender,
                                  avatarBytes: pickedImageBytes,
                                  avatarFileName: pickedImageName,
                                );
                                if (context.mounted) {
                                  Navigator.pop(context);
                                  ScaffoldMessenger.of(context).showSnackBar(
                                    const SnackBar(content: Text("Profile updated!"), behavior: SnackBarBehavior.floating),
                                  );
                                }
                              } catch (e) {
                                if (context.mounted) {
                                  ScaffoldMessenger.of(context).showSnackBar(
                                    SnackBar(content: Text(e.toString()), backgroundColor: Colors.red, behavior: SnackBarBehavior.floating),
                                  );
                                }
                              } finally {
                                setModalState(() {
                                  isSaving = false;
                                });
                              }
                            },
                      style: ElevatedButton.styleFrom(
                        backgroundColor: const Color(0xfffc7e1a),
                        padding: const EdgeInsets.symmetric(vertical: 18),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(15)),
                      ),
                      child: isSaving
                          ? const SizedBox(
                              width: 20,
                              height: 20,
                              child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2),
                            )
                          : const Text("Save Changes", style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
                    ),
                  ),
                ],
              ),
            );
          },
        );
      },
    );
  }

  Widget _buildTextField(String label, TextEditingController controller, IconData icon, {bool isNumber = false}) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(label, style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Colors.grey)),
        const SizedBox(height: 8),
        TextField(
          controller: controller,
          keyboardType: isNumber ? TextInputType.number : TextInputType.text,
          decoration: InputDecoration(
            prefixIcon: Icon(icon, color: const Color(0xfffc7e1a), size: 20),
            filled: true,
            fillColor: Colors.grey.shade100,
            border: OutlineInputBorder(borderRadius: BorderRadius.circular(15), borderSide: BorderSide.none),
          ),
        ),
      ],
    );
  }

  @override
  Widget build(BuildContext context) {
    final appState = Provider.of<AppState>(context);

    return Scaffold(
      extendBody: true,
      backgroundColor: const Color(0xFFF8F9FA),
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        elevation: 0,
        centerTitle: true,
        title: const Text("My Profile", style: TextStyle(color: Colors.black, fontWeight: FontWeight.bold)),
        actions: [
          IconButton(
            icon: const Icon(Icons.settings_outlined, color: Colors.black),
            onPressed: () {
              Navigator.push(context, MaterialPageRoute(builder: (context) => const SettingsPage()));
            },
          ),
        ],
      ),
      body: SingleChildScrollView(
        physics: const BouncingScrollPhysics(),
        padding: const EdgeInsets.only(bottom: 120),
        child: Column(
          children: [
            const SizedBox(height: 20),
            // Profile Header
            Stack(
              alignment: Alignment.bottomRight,
              children: [
                Container(
                  padding: const EdgeInsets.all(4),
                  decoration: BoxDecoration(shape: BoxShape.circle, border: Border.all(color: const Color(0xfffc7e1a), width: 2)),
                  child: CircleAvatar(
                    radius: 50,
                    backgroundColor: Colors.white,
                    backgroundImage: (appState.userAvatar.isEmpty || appState.userAvatar == AppState.defaultAvatarUrl)
                        ? const AssetImage("assets/images/logo.png") as ImageProvider
                        : NetworkImage(appState.userAvatar) as ImageProvider,
                  ),
                ),
                GestureDetector(
                  onTap: () => _showEditProfileModal(context, appState),
                  child: Container(
                    padding: const EdgeInsets.all(8),
                    decoration: const BoxDecoration(color: Color(0xfffc7e1a), shape: BoxShape.circle),
                    child: const Icon(Icons.edit, color: Colors.white, size: 16),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 15),
            Text(appState.userName, style: const TextStyle(fontSize: 22, fontWeight: FontWeight.bold)),
            Text(appState.userEmail, style: TextStyle(color: Colors.grey.shade600, fontSize: 14)),
            
            const SizedBox(height: 30),
            
            // Stats Row
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 25),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  _buildStatCard("Weight", "${appState.userWeight.toStringAsFixed(0)}kg", Icons.monitor_weight_outlined, Colors.orange),
                  _buildStatCard("Height", "${appState.userHeight.toStringAsFixed(0)}cm", Icons.height, Colors.blue),
                  _buildStatCard("BMI (Rec)", appState.userBMI.toStringAsFixed(1), Icons.face, Colors.green),
                ],
              ),
            ),
            
            const SizedBox(height: 35),
            
            // Menu Items
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 25),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text("Account Management", style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Colors.black87)),
                  const SizedBox(height: 15),
                  _buildMenuTile(
                    "Personal Information", 
                    "Edit your health data", 
                    Icons.person_outline, 
                    () => _showEditProfileModal(context, appState)
                  ),
                  _buildMenuTile(
                    "My Favorites", 
                    "Check your saved meals", 
                    Icons.favorite_outline, 
                    () {
                      Navigator.push(context, MaterialPageRoute(builder: (context) => const FavoritesPage()));
                    }
                  ),
                  _buildMenuTile(
                    "Privacy & Security", 
                    "Manage your data", 
                    Icons.security_outlined, 
                    () {
                      Navigator.push(context, MaterialPageRoute(builder: (context) => const PrivacySecurityPage()));
                    }
                  ),
                  
                  const SizedBox(height: 30),
                  
                  GestureDetector(
                    onTap: () async {
                      await Provider.of<AppState>(context, listen: false).logout();
                      if (context.mounted) {
                        Navigator.pushAndRemoveUntil(
                          context, 
                          MaterialPageRoute(builder: (context) => const Auth()),
                          (route) => false,
                        );
                      }
                    },
                    child: Container(
                      width: double.infinity,
                      padding: const EdgeInsets.symmetric(vertical: 15),
                      decoration: BoxDecoration(
                        color: Colors.red.shade50,
                        borderRadius: BorderRadius.circular(15),
                      ),
                      child: Center(
                        child: Row(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Icon(Icons.logout, color: Colors.red.shade700, size: 20),
                            const SizedBox(width: 10),
                            Text("Logout Account", style: TextStyle(color: Colors.red.shade700, fontWeight: FontWeight.bold)),
                          ],
                        ),
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
      bottomNavigationBar: _buildBottomNavigationBar(context),
    );
  }

  Widget _buildStatCard(String label, String value, IconData icon, Color color) {
    return Container(
      width: 100,
      padding: const EdgeInsets.symmetric(vertical: 15),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(20),
        boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.03), blurRadius: 10, offset: const Offset(0, 5))],
      ),
      child: Column(
        children: [
          Icon(icon, color: color, size: 24),
          const SizedBox(height: 8),
          Text(value, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
          Text(label, style: const TextStyle(color: Colors.grey, fontSize: 11, fontWeight: FontWeight.w500)),
        ],
      ),
    );
  }

  Widget _buildMenuTile(String title, String subtitle, IconData icon, VoidCallback onTap) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        margin: const EdgeInsets.only(bottom: 15),
        padding: const EdgeInsets.all(18),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(20),
          boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.02), blurRadius: 10, offset: const Offset(0, 4))],
        ),
        child: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(10),
              decoration: BoxDecoration(color: const Color(0xfffc7e1a).withOpacity(0.1), borderRadius: BorderRadius.circular(12)),
              child: Icon(icon, color: const Color(0xfffc7e1a), size: 22),
            ),
            const SizedBox(width: 15),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(title, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15)),
                  Text(subtitle, style: TextStyle(color: Colors.grey.shade500, fontSize: 12)),
                ],
              ),
            ),
            Icon(Icons.arrow_forward_ios, color: Colors.grey.shade300, size: 16),
          ],
        ),
      ),
    );
  }

  Widget _buildBottomNavigationBar(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(left: 24, right: 24, bottom: 24),
      height: 72,
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(36),
        boxShadow: [
          BoxShadow(color: const Color(0xfffc7e1a).withOpacity(0.15), blurRadius: 25, spreadRadius: 2, offset: const Offset(0, 10)),
          BoxShadow(color: Colors.black.withOpacity(0.04), blurRadius: 15, offset: const Offset(0, 4)),
        ],
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceEvenly,
        children: [
          _buildNavItem(context, Icons.home_rounded, "Home", false, () => Navigator.push(context, MaterialPageRoute(builder: (context) => const HomePage()))),
          _buildNavItem(context, Icons.search_rounded, "Search", false, () => Navigator.push(context, MaterialPageRoute(builder: (context) => FoodDatabasePage()))),
          _buildNavItem(context, Icons.chat_bubble_rounded, "Chat", false, () => Navigator.push(context, MaterialPageRoute(builder: (context) => const ChatPage()))),
          _buildNavItem(context, Icons.person_rounded, "Profile", true, () {}),
          _buildNavItem(context, Icons.settings_rounded, "Settings", false, () => Navigator.push(context, MaterialPageRoute(builder: (context) => const SettingsPage()))),
        ],
      ),
    );
  }

  Widget _buildNavItem(BuildContext context, IconData icon, String label, bool isSelected, VoidCallback onTap) {
    return GestureDetector(
      onTap: onTap,
      behavior: HitTestBehavior.opaque,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 250),
        curve: Curves.easeOutCubic,
        padding: isSelected ? const EdgeInsets.symmetric(horizontal: 16, vertical: 12) : const EdgeInsets.all(12),
        decoration: BoxDecoration(
          color: isSelected ? const Color(0xfffc7e1a).withOpacity(0.1) : Colors.transparent,
          borderRadius: BorderRadius.circular(24),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(icon, size: 26, color: isSelected ? const Color(0xfffc7e1a) : Colors.black38),
            if (isSelected) ...[
              const SizedBox(width: 8),
              Text(label, style: const TextStyle(color: Color(0xfffc7e1a), fontWeight: FontWeight.w800, fontSize: 14)),
            ],
          ],
        ),
      ),
    );
  }
}