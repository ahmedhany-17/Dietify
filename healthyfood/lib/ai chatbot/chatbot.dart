import 'package:flutter/material.dart';
import 'package:healthyfood/database/database.dart';
import 'package:healthyfood/home/home.dart';
import 'package:healthyfood/profile/profile.dart';
import 'package:healthyfood/setting/setting.dart';

class ChatMessage {
  final String text;
  final bool isUser;
  final DateTime time;

  ChatMessage({required this.text, required this.isUser, required this.time});
}

class ChatPage extends StatefulWidget {
  const ChatPage({super.key});

  @override
  State<ChatPage> createState() => _ChatPageState();
}

class _ChatPageState extends State<ChatPage> {
  final List<ChatMessage> _messages = [
    ChatMessage(
      text: "Hello! I'm your AI Nutrition Assistant. What did you eat today?",
      isUser: false,
      time: DateTime.now().subtract(const Duration(minutes: 5)),
    ),
  ];
  final TextEditingController _controller = TextEditingController();
  final ScrollController _scrollController = ScrollController();

  void _sendMessage() {
    if (_controller.text.trim().isEmpty) return;

    final userMessage = _controller.text;
    setState(() {
      _messages.add(ChatMessage(
        text: userMessage,
        isUser: true,
        time: DateTime.now(),
      ));
      _controller.clear();
    });

    _scrollToBottom();
    _simulateBotResponse(userMessage);
  }

  void _simulateBotResponse(String query) {
    String response = "That sounds interesting! Let me check the nutrition data for you.";
    String lowercaseQuery = query.toLowerCase();

    if (lowercaseQuery.contains("egg")) {
      response = "A boiled egg has about 70 calories and 6g of protein. Great choice for a healthy snack!";
    } else if (lowercaseQuery.contains("avocado")) {
      response = "A medium avocado has around 240 calories and is packed with healthy fats!";
    } else if (lowercaseQuery.contains("banana")) {
      response = "A medium banana has about 105 calories and is a great source of potassium.";
    } else if (lowercaseQuery.contains("rice")) {
      response = "1 cup of cooked rice has around 200 calories. Try brown rice for more fiber!";
    } else if (lowercaseQuery.contains("chicken")) {
      response = "Grilled chicken breast is very healthy! It has about 165 calories per 100g and is high in protein.";
    } else if (lowercaseQuery.contains("pizza")) {
      response = "Pizza can be high in calories (about 250-300 per slice). Try a thin crust with lots of veggies!";
    } else if (lowercaseQuery.contains("apple")) {
      response = "An apple a day! A medium apple has about 95 calories and plenty of fiber.";
    }

    Future.delayed(const Duration(seconds: 1), () {
      if (!mounted) return;
      setState(() {
        _messages.add(ChatMessage(
          text: response,
          isUser: false,
          time: DateTime.now(),
        ));
      });
      _scrollToBottom();
    });
  }

  void _scrollToBottom() {
    Future.delayed(const Duration(milliseconds: 100), () {
      if (_scrollController.hasClients) {
        _scrollController.animateTo(
          _scrollController.position.maxScrollExtent,
          duration: const Duration(milliseconds: 300),
          curve: Curves.easeOut,
        );
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      extendBody: true,
      backgroundColor: const Color(0xFFF8F9FA),
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0,
        centerTitle: true,
        leading: IconButton(
          onPressed: () => Navigator.pop(context),
          icon: const Icon(Icons.arrow_back_ios_new, color: Colors.black, size: 20),
        ),
        title: Column(
          children: [
            const Text("Food Assistant", style: TextStyle(color: Colors.black, fontWeight: FontWeight.bold, fontSize: 18)),
            Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                Container(
                  width: 8,
                  height: 8,
                  decoration: const BoxDecoration(color: Colors.green, shape: BoxShape.circle),
                ),
                const SizedBox(width: 5),
                const Text("Online", style: TextStyle(color: Colors.green, fontSize: 12, fontWeight: FontWeight.w600)),
              ],
            ),
          ],
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.info_outline, color: Colors.black),
            onPressed: () {},
          ),
        ],
      ),
      body: Column(
        children: [
          Expanded(
            child: ListView.builder(
              controller: _scrollController,
              padding: const EdgeInsets.all(20),
              itemCount: _messages.length,
              itemBuilder: (context, index) {
                final msg = _messages[index];
                return _buildMessageBubble(msg);
              },
            ),
          ),
          _buildInputArea(),
          const SizedBox(height: 110), // Space for bottom nav
        ],
      ),
      bottomNavigationBar: _buildBottomNavigationBar(context),
    );
  }

  Widget _buildMessageBubble(ChatMessage msg) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 20),
      child: Row(
        mainAxisAlignment: msg.isUser ? MainAxisAlignment.end : MainAxisAlignment.start,
        crossAxisAlignment: CrossAxisAlignment.end,
        children: [
          if (!msg.isUser)
            Container(
              margin: const EdgeInsets.only(right: 10),
              padding: const EdgeInsets.all(8),
              decoration: const BoxDecoration(color: Color(0xfffc7e1a), shape: BoxShape.circle),
              child: const Icon(Icons.auto_awesome, color: Colors.white, size: 16),
            ),
          Flexible(
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
              decoration: BoxDecoration(
                color: msg.isUser ? const Color(0xfffc7e1a) : Colors.white,
                borderRadius: BorderRadius.only(
                  topLeft: const Radius.circular(20),
                  topRight: const Radius.circular(20),
                  bottomLeft: Radius.circular(msg.isUser ? 20 : 0),
                  bottomRight: Radius.circular(msg.isUser ? 0 : 20),
                ),
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withOpacity(0.05),
                    blurRadius: 10,
                    offset: const Offset(0, 5),
                  ),
                ],
              ),
              child: Text(
                msg.text,
                style: TextStyle(
                  color: msg.isUser ? Colors.white : Colors.black87,
                  fontSize: 15,
                  height: 1.4,
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildInputArea() {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 15),
      decoration: BoxDecoration(
        color: Colors.white,
        boxShadow: [
          BoxShadow(color: Colors.black.withOpacity(0.05), blurRadius: 20, offset: const Offset(0, -5)),
        ],
      ),
      child: Row(
        children: [
          Expanded(
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 16),
              decoration: BoxDecoration(
                color: Colors.grey.shade100,
                borderRadius: BorderRadius.circular(25),
              ),
              child: TextField(
                controller: _controller,
                onSubmitted: (_) => _sendMessage(),
                decoration: const InputDecoration(
                  hintText: "Ask about calories or diets...",
                  border: InputBorder.none,
                  hintStyle: TextStyle(color: Colors.grey),
                ),
              ),
            ),
          ),
          const SizedBox(width: 15),
          GestureDetector(
            onTap: _sendMessage,
            child: Container(
              padding: const EdgeInsets.all(12),
              decoration: const BoxDecoration(color: Color(0xfffc7e1a), shape: BoxShape.circle),
              child: const Icon(Icons.send_rounded, color: Colors.white, size: 24),
            ),
          ),
        ],
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
          _buildNavItem(context, Icons.chat_bubble_rounded, "Chat", true, () {}),
          _buildNavItem(context, Icons.person_rounded, "Profile", false, () => Navigator.push(context, MaterialPageRoute(builder: (context) => const ProfilePage()))),
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
