import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:healthyfood/providers/app_state.dart';

class ChangePasswordPage extends StatefulWidget {
  const ChangePasswordPage({super.key});

  @override
  State<ChangePasswordPage> createState() => _ChangePasswordPageState();
}

class _ChangePasswordPageState extends State<ChangePasswordPage> {
  bool _obscureOld = true;
  bool _obscureNew = true;
  bool _obscureConfirm = true;
  
  final _oldController = TextEditingController();
  final _newController = TextEditingController();
  final _confirmController = TextEditingController();
  
  final _formKey = GlobalKey<FormState>();
  bool _isSaving = false;

  @override
  void dispose() {
    _oldController.dispose();
    _newController.dispose();
    _confirmController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xffF5F5F5),
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        elevation: 0,
        centerTitle: true,
        iconTheme: const IconThemeData(color: Colors.black),
        title: const Text("Change Password", style: TextStyle(color: Colors.black, fontWeight: FontWeight.bold)),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(25),
        child: Form(
          key: _formKey,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Center(
                child: Column(
                  children: [
                    Icon(Icons.lock_reset_rounded, size: 80, color: Color(0xfffc7e1a)),
                    SizedBox(height: 15),
                    Text("Secure Your Account", style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                    SizedBox(height: 5),
                    Text("Set a strong password to stay safe.", style: TextStyle(color: Colors.grey, fontSize: 13)),
                  ],
                ),
              ),
              const SizedBox(height: 40),
              
              _buildPasswordField(
                "Current Password", 
                _oldController, 
                _obscureOld, 
                () => setState(() => _obscureOld = !_obscureOld),
                (v) {
                  if (v == null || v.isEmpty) return "Current password is required";
                  return null;
                }
              ),
              const SizedBox(height: 20),
              _buildPasswordField(
                "New Password", 
                _newController, 
                _obscureNew, 
                () => setState(() => _obscureNew = !_obscureNew),
                (v) {
                  if (v == null || v.isEmpty) return "New password is required";
                  if (v.length < 6) return "Password must be at least 6 characters";
                  return null;
                }
              ),
              const SizedBox(height: 20),
              _buildPasswordField(
                "Confirm New Password", 
                _confirmController, 
                _obscureConfirm, 
                () => setState(() => _obscureConfirm = !_obscureConfirm),
                (v) {
                  if (v == null || v.isEmpty) return "Please confirm your new password";
                  if (v != _newController.text) return "Passwords do not match";
                  return null;
                }
              ),
              
              const SizedBox(height: 40),
              
              SizedBox(
                width: double.infinity,
                child: ElevatedButton(
                  onPressed: _isSaving
                      ? null
                      : () async {
                          if (_formKey.currentState!.validate()) {
                            setState(() {
                              _isSaving = true;
                            });
                            try {
                              await Provider.of<AppState>(context, listen: false).changePassword(
                                _oldController.text,
                                _newController.text,
                              );
                              if (context.mounted) {
                                Navigator.pop(context);
                                ScaffoldMessenger.of(context).showSnackBar(
                                  const SnackBar(content: Text("Password changed successfully!"), behavior: SnackBarBehavior.floating),
                                );
                              }
                            } catch (e) {
                              if (context.mounted) {
                                ScaffoldMessenger.of(context).showSnackBar(
                                  SnackBar(content: Text(e.toString().replaceAll("Exception: ", "")), backgroundColor: Colors.red, behavior: SnackBarBehavior.floating),
                                );
                              }
                            } finally {
                              if (mounted) {
                                setState(() {
                                  _isSaving = false;
                                });
                              }
                            }
                          }
                        },
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xfffc7e1a),
                    padding: const EdgeInsets.symmetric(vertical: 18),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
                    elevation: 5,
                    shadowColor: const Color(0xfffc7e1a).withOpacity(0.3),
                  ),
                  child: _isSaving
                      ? const SizedBox(
                          width: 20,
                          height: 20,
                          child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2),
                        )
                      : const Text("Change Password", style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 16)),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildPasswordField(
    String label, 
    TextEditingController controller, 
    bool isObscure, 
    VoidCallback onToggle,
    FormFieldValidator<String> validator,
  ) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(label, style: const TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: Colors.grey)),
        const SizedBox(height: 10),
        TextFormField(
          controller: controller,
          obscureText: isObscure,
          validator: validator,
          decoration: InputDecoration(
            prefixIcon: const Icon(Icons.lock_outline_rounded, color: Color(0xfffc7e1a)),
            suffixIcon: IconButton(
              icon: Icon(isObscure ? Icons.visibility_off_outlined : Icons.visibility_outlined, color: Colors.grey),
              onPressed: onToggle,
            ),
            filled: true,
            fillColor: Colors.white,
            hintText: "••••••••",
            border: OutlineInputBorder(borderRadius: BorderRadius.circular(20), borderSide: BorderSide.none),
            enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(20), borderSide: BorderSide.none),
            focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(20), borderSide: const BorderSide(color: Color(0xfffc7e1a))),
          ),
        ),
      ],
    );
  }
}
