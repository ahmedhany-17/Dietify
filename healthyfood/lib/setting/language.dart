import 'package:flutter/material.dart';

class LanguagePage extends StatelessWidget {
  const LanguagePage({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xfff5f5f5),
      appBar: AppBar(
        backgroundColor: Color(0xfffc7e1a),
        elevation: 0,
        title: const Text(
          'Language',
          style: TextStyle(
            fontWeight: FontWeight.bold,
            fontSize: 26,
            color: Colors.white,
          ),
        ),
        centerTitle: true,
        foregroundColor: Colors.white,
      ),
      body: Padding(
        padding: const EdgeInsets.all(16),
        child: Container(
          width: double.infinity,
          padding: const EdgeInsets.all(20),
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(18),
            boxShadow: [
              BoxShadow(
                color: Colors.black.withOpacity(0.08),
                blurRadius: 15,
                offset: const Offset(0, 8),
              ),
            ],
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                "Choose Language",
                style: TextStyle(
                  fontSize: 20,
                  fontWeight: FontWeight.w600,
                  color: Color(0xfffc7e1a),
                ),
              ),
              const SizedBox(height: 20),

              Row(
                children: const [
                  Icon(Icons.language, color: Color(0xfffc7e1a),),
                  SizedBox(width: 12),
                  Expanded(
                    child: Text(
                      "English",
                      style: TextStyle(
                        fontSize: 18,
                        fontWeight: FontWeight.w500,
                      ),
                    ),
                  ),
                  Icon(Icons.check_circle, color: Color(0xfffc7e1a),
                  )],
              ),

              const SizedBox(height: 16),
              Divider(),

              const SizedBox(height: 16),

              Row(
                children: const [
                  Icon(Icons.language, color: Colors.grey),
                  SizedBox(width: 12),
                  Expanded(
                    child: Text(
                      "Arabic",
                      style: TextStyle(
                        fontSize: 18,
                        fontWeight: FontWeight.w500,
                      ),
                    ),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }
}
