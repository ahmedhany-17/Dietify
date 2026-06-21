import 'package:flutter/material.dart';
import 'package:healthyfood/cart/cart.dart';
import 'package:healthyfood/database/database.dart';
import 'package:healthyfood/home/bmi/bmi.dart';
import 'package:healthyfood/recommendation/recommendation.dart';
import 'package:healthyfood/models/product.dart';

import '../ai chatbot/chatbot.dart';
import '../profile/profile.dart';
import '../setting/setting.dart';
import 'package:provider/provider.dart';
import 'package:healthyfood/providers/app_state.dart';

class HomePage extends StatelessWidget {
  const HomePage({super.key});

  String _getGreeting() {
    var hour = DateTime.now().hour;
    if (hour < 12) return 'Good Morning';
    if (hour < 17) return 'Good Afternoon';
    return 'Good Evening';
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF8F9FB),
      extendBody: true,
      body: SafeArea(
        bottom: false,
        child: SingleChildScrollView(
          physics: const BouncingScrollPhysics(),
          padding: const EdgeInsets.only(
            left: 20,
            right: 20,
            top: 15,
            bottom: 110,
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              _buildHeader(context),
              const SizedBox(height: 25),
              _buildGreeting(),
              const SizedBox(height: 25),
              _buildCalorieCard(),
              const SizedBox(height: 25),
              _buildStatsRow(context),
              const SizedBox(height: 30),
              _buildSectionHeader("Recommended for You", () {
                Navigator.push(
                  context,
                  MaterialPageRoute(
                    builder: (context) => const RecommendationScreen(),
                  ),
                );
              }),
              const SizedBox(height: 15),
              _buildMealSuggestions(context),
            ],
          ),
        ),
      ),
      bottomNavigationBar: _buildBottomNavigationBar(context),
    );
  }

  Widget _buildHeader(BuildContext context) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Row(
          children: [
            GestureDetector(
              onTap: () => Navigator.push(
                context,
                MaterialPageRoute(builder: (context) => const ProfilePage()),
              ),
              child: Container(
                padding: const EdgeInsets.all(2),
                decoration: BoxDecoration(
                  shape: BoxShape.circle,
                  border: Border.all(color: const Color(0xfffc7e1a), width: 2),
                ),
                child: Consumer<AppState>(
                  builder: (context, appState, child) {
                    final avatarUrl = appState.userAvatar;
                    final isDefault = avatarUrl.isEmpty || avatarUrl == AppState.defaultAvatarUrl;
                    return CircleAvatar(
                      radius: 22,
                      backgroundColor: Colors.white,
                      backgroundImage: isDefault
                          ? const AssetImage("assets/images/logo.png") as ImageProvider
                          : NetworkImage(avatarUrl) as ImageProvider,
                    );
                  },
                ),
              ),
            ),
            const SizedBox(width: 12),
            const Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  "Dietify",
                  style: TextStyle(
                    fontWeight: FontWeight.w900,
                    fontSize: 22,
                    letterSpacing: -0.5,
                  ),
                ),
                Text(
                  "Your Health Partner",
                  style: TextStyle(
                    color: Colors.grey,
                    fontSize: 12,
                    fontWeight: FontWeight.w500,
                  ),
                ),
              ],
            ),
          ],
        ),
        _buildCartButton(context),
      ],
    );
  }

  Widget _buildCartButton(BuildContext context) {
    return Stack(
      children: [
        Container(
          decoration: BoxDecoration(
            color: Colors.white,
            shape: BoxShape.circle,
            boxShadow: [
              BoxShadow(
                color: Colors.black.withOpacity(0.04),
                blurRadius: 10,
                offset: const Offset(0, 4),
              ),
            ],
          ),
          child: IconButton(
            onPressed: () => Navigator.push(
              context,
              MaterialPageRoute(builder: (context) => const MyCartScreen()),
            ),
            icon: const Icon(
              Icons.shopping_bag_outlined,
              color: Colors.black87,
            ),
          ),
        ),
        Consumer<AppState>(
          builder: (context, appState, child) {
            if (appState.cartItemCount == 0) return const SizedBox.shrink();
            return Positioned(
              right: 4,
              top: 4,
              child: CircleAvatar(
                radius: 8,
                backgroundColor: const Color(0xfffc7e1a),
                child: Text(
                  '${appState.cartItemCount}',
                  style: const TextStyle(
                    fontSize: 10,
                    color: Colors.white,
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ),
            );
          },
        ),
      ],
    );
  }

  Widget _buildGreeting() {
    return Consumer<AppState>(
      builder: (context, appState, child) => Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            "${_getGreeting()},",
            style: const TextStyle(
              fontSize: 16,
              color: Colors.grey,
              fontWeight: FontWeight.w500,
            ),
          ),
          Text(
            appState.userName,
            style: const TextStyle(
              fontSize: 28,
              fontWeight: FontWeight.bold,
              color: Colors.black87,
              letterSpacing: -0.5,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildCalorieCard() {
    return Consumer<AppState>(
      builder: (context, appState, child) {
        final maintenance = appState.maintenanceCalories;
        final loss = appState.weightLossCalories;
        final gain = appState.weightGainCalories;

        final maintenanceMac = appState.maintenanceMacros;
        final lossMac = appState.weightLossMacros;
        final gainMac = appState.weightGainMacros;

        return Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Top Main Card (Active Calorie Goal)
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(20),
              decoration: BoxDecoration(
                gradient: const LinearGradient(
                  colors: [Color(0xFF1A1F26), Color(0xFF2D3748)],
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                ),
                borderRadius: BorderRadius.circular(24),
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withOpacity(0.1),
                    blurRadius: 15,
                    offset: const Offset(0, 8),
                  ),
                ],
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            "Daily Calorie Goal (${appState.selectedGoalType})",
                            style: const TextStyle(
                              color: Colors.white70,
                              fontSize: 14,
                              fontWeight: FontWeight.w600,
                            ),
                          ),
                          const SizedBox(height: 4),
                          Text(
                            "${appState.selectedGoalCalories.toStringAsFixed(0)} kcal",
                            style: const TextStyle(
                              color: Colors.white,
                              fontSize: 32,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                        ],
                      ),
                      Container(
                        padding: const EdgeInsets.all(10),
                        decoration: BoxDecoration(
                          color: const Color(0xfffc7e1a).withOpacity(0.15),
                          shape: BoxShape.circle,
                        ),
                        child: const Icon(
                          Icons.local_fire_department,
                          color: Color(0xfffc7e1a),
                          size: 28,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 15),
                  const Divider(color: Colors.white12, height: 1),
                  const SizedBox(height: 12),
                  
                  // Consumed/Remaining row inside the main card
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      // Logged Calories container with adjacent Reset Button
                      Row(
                        children: [
                          Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              const Text(
                                "Logged Calories",
                                style: TextStyle(color: Colors.white54, fontSize: 11),
                              ),
                              const SizedBox(height: 2),
                              Text(
                                "${appState.caloriesConsumed.toInt()} kcal",
                                style: const TextStyle(
                                  color: Colors.white,
                                  fontSize: 16,
                                  fontWeight: FontWeight.bold,
                                ),
                              ),
                            ],
                          ),
                          const SizedBox(width: 8),
                          // Reset Icon Button
                          GestureDetector(
                            onTap: () {
                              appState.resetCaloriesConsumed();
                              ScaffoldMessenger.of(context).showSnackBar(
                                const SnackBar(
                                  content: Text("Logged calories reset to 0 kcal!"),
                                  behavior: SnackBarBehavior.floating,
                                  duration: Duration(seconds: 1),
                                ),
                              );
                            },
                            child: Container(
                              padding: const EdgeInsets.all(4),
                              decoration: BoxDecoration(
                                color: Colors.white.withOpacity(0.1),
                                shape: BoxShape.circle,
                              ),
                              child: const Icon(
                                Icons.refresh_rounded,
                                color: Color(0xfffc7e1a),
                                size: 16,
                              ),
                            ),
                          ),
                        ],
                      ),
                      
                      // Remaining / Overgoal Container inside main card
                      Builder(
                        builder: (context) {
                          final remaining = appState.selectedGoalCalories - appState.caloriesConsumed;
                          final isOver = remaining < 0;
                          return Column(
                            crossAxisAlignment: CrossAxisAlignment.end,
                            children: [
                              Text(
                                isOver ? "Overgoal" : "Remaining",
                                style: TextStyle(
                                  color: isOver ? const Color(0xFFFF5252) : Colors.white54,
                                  fontSize: 11,
                                  fontWeight: isOver ? FontWeight.bold : FontWeight.normal,
                                ),
                              ),
                              const SizedBox(height: 2),
                              Text(
                                isOver
                                    ? "Over by ${remaining.abs().toInt()} kcal"
                                    : "${remaining.toInt()} kcal",
                                style: TextStyle(
                                  color: isOver ? const Color(0xFFFF5252) : const Color(0xFF10B981),
                                  fontSize: 16,
                                  fontWeight: FontWeight.bold,
                                ),
                              ),
                            ],
                          );
                        },
                      ),
                    ],
                  ),
                  
                  const SizedBox(height: 12),
                  const Divider(color: Colors.white12, height: 1),
                  const SizedBox(height: 12),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text(
                        "Gender: ${appState.userGender}  |  Age: ${appState.userAge}  |  Weight: ${appState.userWeight.toStringAsFixed(0)} kg",
                        style: const TextStyle(
                          color: Colors.white54,
                          fontSize: 12,
                        ),
                      ),
                      const Icon(
                        Icons.auto_awesome,
                        color: Colors.amber,
                        size: 14,
                      ),
                    ],
                  ),
                ],
              ),
            ),
            const SizedBox(height: 15),
            
            // Three side-by-side cards
            Row(
              children: [
                // Weight Loss
                Expanded(
                  child: GestureDetector(
                    onTap: () => appState.setSelectedGoalType("Weight Loss"),
                    child: Container(
                      padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 15),
                      decoration: BoxDecoration(
                        color: appState.selectedGoalType == "Weight Loss"
                            ? Colors.orange.shade50.withOpacity(0.3)
                            : Colors.white,
                        borderRadius: BorderRadius.circular(20),
                        border: Border.all(
                          color: appState.selectedGoalType == "Weight Loss"
                              ? Colors.orange.shade600
                              : Colors.orange.shade100,
                          width: appState.selectedGoalType == "Weight Loss" ? 3.0 : 1.5,
                        ),
                        boxShadow: [
                          BoxShadow(
                            color: appState.selectedGoalType == "Weight Loss"
                                ? Colors.orange.shade200.withOpacity(0.5)
                                : Colors.orange.shade50.withOpacity(0.5),
                            blurRadius: 8,
                            offset: const Offset(0, 4),
                          ),
                        ],
                      ),
                      child: Column(
                        children: [
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                            decoration: BoxDecoration(
                              color: Colors.orange.shade50,
                              borderRadius: BorderRadius.circular(10),
                            ),
                            child: Text(
                              "Weight Loss",
                              style: TextStyle(
                                color: Colors.orange.shade800,
                                fontSize: 10,
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                          ),
                          const SizedBox(height: 10),
                          Text(
                            loss.toStringAsFixed(0),
                            style: const TextStyle(
                              fontSize: 18,
                              fontWeight: FontWeight.bold,
                              color: Colors.black87,
                            ),
                          ),
                          const Text(
                            "kcal/day",
                            style: TextStyle(fontSize: 9, color: Colors.grey),
                          ),
                          const SizedBox(height: 10),
                          const Divider(height: 1, thickness: 0.5),
                          const SizedBox(height: 8),
                          Text(
                            "P: ${lossMac['protein']}g\nC: ${lossMac['carbs']}g\nF: ${lossMac['fats']}g",
                            textAlign: TextAlign.center,
                            style: const TextStyle(
                              fontSize: 10,
                              color: Colors.black54,
                              height: 1.4,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                ),
                const SizedBox(width: 8),

                // Maintenance
                Expanded(
                  child: GestureDetector(
                    onTap: () => appState.setSelectedGoalType("Maintenance"),
                    child: Container(
                      padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 15),
                      decoration: BoxDecoration(
                        color: appState.selectedGoalType == "Maintenance"
                            ? Colors.green.shade50.withOpacity(0.3)
                            : Colors.white,
                        borderRadius: BorderRadius.circular(20),
                        border: Border.all(
                          color: appState.selectedGoalType == "Maintenance"
                              ? Colors.green.shade600
                              : Colors.green.shade100,
                          width: appState.selectedGoalType == "Maintenance" ? 3.0 : 1.5,
                        ),
                        boxShadow: [
                          BoxShadow(
                            color: appState.selectedGoalType == "Maintenance"
                                ? Colors.green.shade200.withOpacity(0.5)
                                : Colors.green.shade50.withOpacity(0.5),
                            blurRadius: 8,
                            offset: const Offset(0, 4),
                          ),
                        ],
                      ),
                      child: Column(
                        children: [
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                            decoration: BoxDecoration(
                              color: Colors.green.shade50,
                              borderRadius: BorderRadius.circular(10),
                            ),
                            child: Text(
                              "Maintenance",
                              style: TextStyle(
                                color: Colors.green.shade800,
                                fontSize: 10,
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                          ),
                          const SizedBox(height: 10),
                          Text(
                            maintenance.toStringAsFixed(0),
                            style: const TextStyle(
                              fontSize: 18,
                              fontWeight: FontWeight.bold,
                              color: Colors.black87,
                            ),
                          ),
                          const Text(
                            "kcal/day",
                            style: TextStyle(fontSize: 9, color: Colors.grey),
                          ),
                          const SizedBox(height: 10),
                          const Divider(height: 1, thickness: 0.5),
                          const SizedBox(height: 8),
                          Text(
                            "P: ${maintenanceMac['protein']}g\nC: ${maintenanceMac['carbs']}g\nF: ${maintenanceMac['fats']}g",
                            textAlign: TextAlign.center,
                            style: const TextStyle(
                              fontSize: 10,
                              color: Colors.black54,
                              height: 1.4,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                ),
                const SizedBox(width: 8),

                // Weight Gain
                Expanded(
                  child: GestureDetector(
                    onTap: () => appState.setSelectedGoalType("Weight Gain"),
                    child: Container(
                      padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 15),
                      decoration: BoxDecoration(
                        color: appState.selectedGoalType == "Weight Gain"
                            ? Colors.blue.shade50.withOpacity(0.3)
                            : Colors.white,
                        borderRadius: BorderRadius.circular(20),
                        border: Border.all(
                          color: appState.selectedGoalType == "Weight Gain"
                              ? Colors.blue.shade600
                              : Colors.blue.shade100,
                          width: appState.selectedGoalType == "Weight Gain" ? 3.0 : 1.5,
                        ),
                        boxShadow: [
                          BoxShadow(
                            color: appState.selectedGoalType == "Weight Gain"
                                ? Colors.blue.shade200.withOpacity(0.5)
                                : Colors.blue.shade50.withOpacity(0.5),
                            blurRadius: 8,
                            offset: const Offset(0, 4),
                          ),
                        ],
                      ),
                      child: Column(
                        children: [
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                            decoration: BoxDecoration(
                              color: Colors.blue.shade50,
                              borderRadius: BorderRadius.circular(10),
                            ),
                            child: Text(
                              "Weight Gain",
                              style: TextStyle(
                                color: Colors.blue.shade800,
                                fontSize: 10,
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                          ),
                          const SizedBox(height: 10),
                          Text(
                            gain.toStringAsFixed(0),
                            style: const TextStyle(
                              fontSize: 18,
                              fontWeight: FontWeight.bold,
                              color: Colors.black87,
                            ),
                          ),
                          const Text(
                            "kcal/day",
                            style: TextStyle(fontSize: 9, color: Colors.grey),
                          ),
                          const SizedBox(height: 10),
                          const Divider(height: 1, thickness: 0.5),
                          const SizedBox(height: 8),
                          Text(
                            "P: ${gainMac['protein']}g\nC: ${gainMac['carbs']}g\nF: ${gainMac['fats']}g",
                            textAlign: TextAlign.center,
                            style: const TextStyle(
                              fontSize: 10,
                              color: Colors.black54,
                              height: 1.4,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                ),
              ],
            ),
          ],
        );
      },
    );
  }

  Widget _buildStatsRow(BuildContext context) {
    return Consumer<AppState>(
      builder: (context, appState, child) {
        final remaining = appState.selectedGoalCalories - appState.caloriesConsumed;
        final isOver = remaining < 0;
        return Row(
          children: [
            Expanded(
              child: _buildStatCard(
                "BMI (Rec)",
                appState.userBMI.toStringAsFixed(1),
                Icons.monitor_weight_outlined,
                const Color(0xFF6366F1),
                () {
                  Navigator.push(
                    context,
                    MaterialPageRoute(builder: (context) => const BMIScreen()),
                  );
                },
              ),
            ),
            const SizedBox(width: 15),
            Expanded(
              child: _buildStatCard(
                isOver ? "Over Goal" : "Remaining",
                isOver ? "Over by ${remaining.abs().toInt()} kcal" : "${remaining.toInt()} kcal",
                isOver ? Icons.warning_amber_rounded : Icons.pie_chart_outline_rounded,
                isOver ? const Color(0xFFFF5252) : const Color(0xFF10B981),
                () {
                  _showAddCaloriesDialog(context, appState);
                },
                isOverGoal: isOver,
              ),
            ),
          ],
        );
      },
    );
  }

  Widget _buildStatCard(
    String title,
    String value,
    IconData icon,
    Color color,
    VoidCallback onTap, {
    bool isOverGoal = false,
  }) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.all(20),
        decoration: BoxDecoration(
          color: isOverGoal ? const Color(0xFFFFEBEE) : Colors.white,
          border: isOverGoal ? Border.all(color: const Color(0xFFFF5252), width: 1.5) : null,
          borderRadius: BorderRadius.circular(25),
          boxShadow: [
            BoxShadow(
              color: isOverGoal
                  ? const Color(0xFFFF5252).withOpacity(0.1)
                  : Colors.black.withOpacity(0.03),
              blurRadius: 15,
              offset: const Offset(0, 8),
            ),
          ],
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Container(
              padding: const EdgeInsets.all(8),
              decoration: BoxDecoration(
                color: color.withOpacity(0.1),
                borderRadius: BorderRadius.circular(12),
              ),
              child: Icon(icon, color: color, size: 20),
            ),
            const SizedBox(height: 15),
            Text(
              title,
              style: TextStyle(
                color: isOverGoal ? const Color(0xFFFF5252) : Colors.grey,
                fontSize: 12,
                fontWeight: FontWeight.bold,
              ),
            ),
            const SizedBox(height: 5),
            Text(
              value,
              style: TextStyle(
                fontSize: 20,
                fontWeight: FontWeight.bold,
                color: isOverGoal ? const Color(0xFFFF5252) : Colors.black87,
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildSectionHeader(String title, VoidCallback onSeeAll) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(
          title,
          style: const TextStyle(
            fontSize: 18,
            fontWeight: FontWeight.bold,
            letterSpacing: -0.5,
          ),
        ),
        TextButton(
          onPressed: onSeeAll,
          child: const Text(
            "See All",
            style: TextStyle(
              color: Color(0xfffc7e1a),
              fontWeight: FontWeight.bold,
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildMealSuggestions(BuildContext context) {
    final appState = Provider.of<AppState>(context);
    final suggestions = appState.foods
        .where((p) => p.type.toLowerCase() == "meal" || p.type.toLowerCase() == "food")
        .take(4)
        .toList();
    return SingleChildScrollView(
      scrollDirection: Axis.horizontal,
      physics: const BouncingScrollPhysics(),
      child: Row(
        children: suggestions
            .map((meal) => _buildMealCard(context, meal))
            .toList(),
      ),
    );
  }

  Widget _buildMealCard(BuildContext context, Product meal) {
    return GestureDetector(
      onTap: () {
        final appState = Provider.of<AppState>(context, listen: false);
        appState.addConsumedCalories(meal.calories);
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text("Logged ${meal.name} (${meal.calories} kcal)!"),
            behavior: SnackBarBehavior.floating,
            backgroundColor: const Color(0xfffc7e1a),
          ),
        );
      },
      child: Container(
        width: 160,
        margin: const EdgeInsets.only(right: 15),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(25),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withOpacity(0.03),
              blurRadius: 10,
              offset: const Offset(0, 5),
            ),
          ],
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Container(
              height: 120,
              decoration: BoxDecoration(
                borderRadius: const BorderRadius.vertical(
                  top: Radius.circular(25),
                ),
                image: DecorationImage(
                  image: NetworkImage(meal.image),
                  fit: BoxFit.cover,
                ),
              ),
            ),
            Padding(
              padding: const EdgeInsets.all(12),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    meal.name,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(
                      fontWeight: FontWeight.bold,
                      fontSize: 14,
                    ),
                  ),
                  const SizedBox(height: 5),
                  Text(
                    "${meal.calories} kcal",
                    style: const TextStyle(
                      color: Color(0xfffc7e1a),
                      fontWeight: FontWeight.bold,
                      fontSize: 12,
                    ),
                  ),
                ],
              ),
            ),
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
          BoxShadow(
            color: const Color(0xfffc7e1a).withOpacity(0.15),
            blurRadius: 25,
            spreadRadius: 2,
            offset: const Offset(0, 10),
          ),
          BoxShadow(
            color: Colors.black.withOpacity(0.04),
            blurRadius: 15,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceEvenly,
        children: [
          _buildNavItem(context, Icons.home_rounded, "Home", true, () {}),
          _buildNavItem(
            context,
            Icons.search_rounded,
            "Search",
            false,
            () => Navigator.push(
              context,
              MaterialPageRoute(builder: (context) => FoodDatabasePage()),
            ),
          ),
          _buildNavItem(
            context,
            Icons.chat_bubble_rounded,
            "Chat",
            false,
            () => Navigator.push(
              context,
              MaterialPageRoute(builder: (context) => const ChatPage()),
            ),
          ),
          _buildNavItem(
            context,
            Icons.person_rounded,
            "Profile",
            false,
            () => Navigator.push(
              context,
              MaterialPageRoute(builder: (context) => const ProfilePage()),
            ),
          ),
          _buildNavItem(
            context,
            Icons.settings_rounded,
            "Settings",
            false,
            () => Navigator.push(
              context,
              MaterialPageRoute(builder: (context) => const SettingsPage()),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildNavItem(
    BuildContext context,
    IconData icon,
    String label,
    bool isSelected,
    VoidCallback onTap,
  ) {
    return GestureDetector(
      onTap: onTap,
      behavior: HitTestBehavior.opaque,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 250),
        curve: Curves.easeOutCubic,
        padding: isSelected
            ? const EdgeInsets.symmetric(horizontal: 16, vertical: 12)
            : const EdgeInsets.all(12),
        decoration: BoxDecoration(
          color: isSelected
              ? const Color(0xfffc7e1a).withOpacity(0.1)
              : Colors.transparent,
          borderRadius: BorderRadius.circular(24),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(
              icon,
              size: 26,
              color: isSelected ? const Color(0xfffc7e1a) : Colors.black38,
            ),
            if (isSelected) ...[
              const SizedBox(width: 8),
              Text(
                label,
                style: const TextStyle(
                  color: Color(0xfffc7e1a),
                  fontWeight: FontWeight.w800,
                  fontSize: 14,
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }

  void _showAddCaloriesDialog(BuildContext context, AppState appState) {
    final calorieController = TextEditingController();
    showDialog(
      context: context,
      builder: (context) {
        return AlertDialog(
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
          title: const Text("Log Calories Manually", style: TextStyle(fontWeight: FontWeight.bold)),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              const Text("Enter the number of calories you consumed:"),
              const SizedBox(height: 15),
              TextField(
                controller: calorieController,
                keyboardType: TextInputType.number,
                autofocus: true,
                decoration: InputDecoration(
                  hintText: "e.g., 250",
                  suffixText: "kcal",
                  filled: true,
                  fillColor: Colors.grey.shade100,
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(15), borderSide: BorderSide.none),
                ),
              ),
            ],
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(context),
              child: const Text("Cancel", style: TextStyle(color: Colors.grey)),
            ),
            ElevatedButton(
              onPressed: () {
                final kcal = int.tryParse(calorieController.text);
                if (kcal != null && kcal > 0) {
                  appState.addConsumedCalories(kcal);
                  Navigator.pop(context);
                  ScaffoldMessenger.of(context).showSnackBar(
                    SnackBar(
                      content: Text("Logged $kcal kcal!"),
                      behavior: SnackBarBehavior.floating,
                      backgroundColor: const Color(0xfffc7e1a),
                    ),
                  );
                }
              },
              style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xfffc7e1a),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
              ),
              child: const Text("Log", style: TextStyle(color: Colors.white)),
            ),
          ],
        );
      },
    );
  }
}
