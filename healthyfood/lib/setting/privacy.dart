import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:healthyfood/providers/app_state.dart';
import 'package:healthyfood/auth/auth.dart';


class PrivacySecurityPage extends StatelessWidget {
  const PrivacySecurityPage({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xffF5F5F5),
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        elevation: 0,
        centerTitle: true,
        iconTheme: const IconThemeData(color: Colors.black),
        title: const Text(
          "Privacy & Security",
          style: TextStyle(color: Colors.black, fontWeight: FontWeight.bold),
        ),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            _buildSectionHeader("Account Security"),
            _buildSecurityTile(Icons.fingerprint, "Biometric Authentication", "Use fingerprint or face ID to login", true),
            _buildSecurityTile(Icons.lock_outline, "Two-Factor Authentication", "Add an extra layer of security", false),
            
            const SizedBox(height: 30),
            _buildSectionHeader("Data Privacy"),
            _buildSecurityTile(Icons.visibility_off_outlined, "Private Profile", "Only friends can see your activity", true),
            _buildSecurityTile(Icons.analytics_outlined, "Usage Analytics", "Help us improve by sharing data", true),
            
            const SizedBox(height: 30),
            _buildSectionHeader("Permissions"),
            _buildSecurityTile(Icons.location_on_outlined, "Location Access", "Used for delivery tracking", true),
            _buildSecurityTile(Icons.camera_alt_outlined, "Camera Access", "Used for profile pictures", true),
            
            const SizedBox(height: 40),
            Center(
              child: TextButton(
                onPressed: () {
                  showDialog(
                    context: context,
                    builder: (dialogCtx) => AlertDialog(
                      title: const Text("Delete Account"),
                      content: const Text("Are you sure you want to permanently delete your account? This action will erase all your profile data, addresses, orders, and payments, and cannot be undone."),
                      actions: [
                        TextButton(
                          onPressed: () => Navigator.pop(dialogCtx),
                          child: const Text("Cancel"),
                        ),
                        TextButton(
                          onPressed: () async {
                            Navigator.pop(dialogCtx); // close dialog
                            showDialog(
                              context: context,
                              barrierDismissible: false,
                              builder: (context) => const Center(child: CircularProgressIndicator(color: Colors.red)),
                            );
                            try {
                              await Provider.of<AppState>(context, listen: false).deleteAccount();
                              if (context.mounted) {
                                Navigator.pop(context); // remove loading
                                Navigator.pushAndRemoveUntil(
                                  context,
                                  MaterialPageRoute(builder: (context) => const Auth()),
                                  (route) => false,
                                );
                                ScaffoldMessenger.of(context).showSnackBar(
                                  const SnackBar(content: Text("Account permanently deleted."), behavior: SnackBarBehavior.floating),
                                );
                              }
                            } catch (e) {
                              if (context.mounted) {
                                Navigator.pop(context); // remove loading
                                ScaffoldMessenger.of(context).showSnackBar(
                                  SnackBar(content: Text(e.toString()), backgroundColor: Colors.red, behavior: SnackBarBehavior.floating),
                                );
                              }
                            }
                          },
                          child: const Text("Delete", style: TextStyle(color: Colors.red)),
                        ),
                      ],
                    ),
                  );
                },
                child: const Text("Delete Account", style: TextStyle(color: Colors.red, fontWeight: FontWeight.bold)),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildSectionHeader(String title) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 10, left: 5),
      child: Text(
        title,
        style: const TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: Colors.grey),
      ),
    );
  }

  Widget _buildSecurityTile(IconData icon, String title, String subtitle, bool isEnabled) {
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(16),
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
          Switch(
            value: isEnabled,
            onChanged: (v) {},
            activeThumbColor: const Color(0xfffc7e1a),
          ),
        ],
      ),
    );
  }
}
