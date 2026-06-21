import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:healthyfood/providers/app_state.dart';
import 'package:healthyfood/models/product.dart';
import 'package:healthyfood/ai chatbot/chatbot.dart';
import 'package:http/http.dart' as http;

class RecommendationScreen extends StatefulWidget {
  const RecommendationScreen({super.key});

  @override
  State<RecommendationScreen> createState() => _RecommendationScreenState();
}

class _RecommendationScreenState extends State<RecommendationScreen> {
  String selectedFilter = "All";
  double maxCalories = 600.0;
  String sortBy = "Recommended";

  List<Product> _recommendations = [];
  bool _isLoading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      _fetchDbRecommendations();
    });
  }

  Future<void> _fetchDbRecommendations() async {
    if (!mounted) return;
    setState(() {
      _isLoading = true;
      _error = null;
    });
    try {
      final response = await http.get(
        Uri.parse('http://127.0.0.1/healthyfood/recommendations_api.php'),
      ).timeout(const Duration(seconds: 10));

      if (!mounted) return;

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data is List) {
          setState(() {
            _recommendations = data.map((item) => Product.fromJson(item)).toList();
            _isLoading = false;
          });
        } else if (data is Map && data['success'] == true && data['recommendations'] != null) {
          final List<dynamic> list = data['recommendations'];
          setState(() {
            _recommendations = list.map((item) => Product.fromJson(item)).toList();
            _isLoading = false;
          });
        } else if (data is Map && data['error'] != null) {
          setState(() {
            _error = data['error'];
            _isLoading = false;
          });
        } else {
          setState(() {
            _error = "Failed to fetch recommendations";
            _isLoading = false;
          });
        }
      } else {
        setState(() {
          _error = "Server error: ${response.statusCode}";
          _isLoading = false;
        });
      }
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _error = e.toString();
        _isLoading = false;
      });
    }
  }

  List<Product> getFilteredFoods(List<Product> sourceFoods) {
    List<Product> meals = sourceFoods.toList();

    // Advanced Filters (Calories)
    meals = meals.where((p) => p.calories <= maxCalories).toList();

    // Sorting
    if (sortBy == "Lowest Calories") {
      meals.sort((a, b) => a.calories.compareTo(b.calories));
    } else if (sortBy == "Highest Protein") {
      meals.sort((a, b) => b.protein.compareTo(a.protein));
    }

    return meals;
  }

  void _showFilterBottomSheet() {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (context) {
        return StatefulBuilder(
          builder: (context, setModalState) {
            return Container(
              height: MediaQuery.of(context).size.height * 0.6,
              decoration: const BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.vertical(top: Radius.circular(30)),
              ),
              padding: const EdgeInsets.all(25),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Center(
                    child: Container(
                      width: 50,
                      height: 5,
                      decoration: BoxDecoration(
                        color: Colors.grey.shade300,
                        borderRadius: BorderRadius.circular(10),
                      ),
                    ),
                  ),
                  const SizedBox(height: 25),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      const Text(
                        "Advanced Filters",
                        style: TextStyle(fontSize: 22, fontWeight: FontWeight.bold),
                      ),
                      TextButton(
                        onPressed: () {
                          setModalState(() {
                            maxCalories = 600.0;
                            sortBy = "Recommended";
                          });
                          setState(() {});
                        },
                        child: const Text("Reset", style: TextStyle(color: Colors.grey)),
                      ),
                    ],
                  ),
                  const SizedBox(height: 30),
                  
                  // Calorie Slider
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      const Text(
                        "Calorie Budget",
                        style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                      ),
                      Text(
                        "${maxCalories.toInt()} kcal",
                        style: const TextStyle(
                          color: Color(0xfffc7e1a),
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 10),
                  SliderTheme(
                    data: SliderTheme.of(context).copyWith(
                      activeTrackColor: const Color(0xfffc7e1a),
                      thumbColor: const Color(0xfffc7e1a),
                      overlayColor: const Color(0xfffc7e1a).withOpacity(0.1),
                    ),
                    child: Slider(
                      value: maxCalories,
                      min: 100,
                      max: 800,
                      onChanged: (value) {
                        setModalState(() => maxCalories = value);
                        setState(() {});
                      },
                    ),
                  ),
                  const SizedBox(height: 30),

                  // Sort Options
                  const Text(
                    "Sort By",
                    style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                  ),
                  const SizedBox(height: 15),
                  Wrap(
                    spacing: 10,
                    children: [
                      _buildSortChip("Recommended", setModalState),
                      _buildSortChip("Lowest Calories", setModalState),
                      _buildSortChip("Highest Protein", setModalState),
                    ],
                  ),
                  
                  const Spacer(),
                  
                  SizedBox(
                    width: double.infinity,
                    child: ElevatedButton(
                      onPressed: () => Navigator.pop(context),
                      style: ElevatedButton.styleFrom(
                        backgroundColor: const Color(0xfffc7e1a),
                        padding: const EdgeInsets.symmetric(vertical: 18),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(15)),
                      ),
                      child: const Text(
                        "Apply Filters",
                        style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 16),
                      ),
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

  Widget _buildSortChip(String label, StateSetter setModalState) {
    bool isSelected = sortBy == label;
    return GestureDetector(
      onTap: () {
        setModalState(() => sortBy = label);
        setState(() {});
      },
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
        decoration: BoxDecoration(
          color: isSelected ? const Color(0xFF1A1F26) : Colors.grey.shade100,
          borderRadius: BorderRadius.circular(15),
          border: Border.all(color: isSelected ? Colors.transparent : Colors.grey.shade200),
        ),
        child: Text(
          label,
          style: TextStyle(
            color: isSelected ? Colors.white : Colors.black87,
            fontWeight: isSelected ? FontWeight.bold : FontWeight.normal,
            fontSize: 13,
          ),
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final appState = Provider.of<AppState>(context);
    final filteredFoods = getFilteredFoods(_recommendations);
    final Product? bestMatch = filteredFoods.isNotEmpty ? filteredFoods.first : null;

    return Scaffold(
      backgroundColor: const Color(0xFFF8F9FA),
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        elevation: 0,
        centerTitle: true,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back_ios_new, color: Colors.black, size: 20),
          onPressed: () => Navigator.pop(context),
        ),
        title: const Text(
          "Food Recommendation",
          style: TextStyle(
            color: Colors.black,
            fontWeight: FontWeight.bold,
            fontSize: 20,
          ),
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.filter_list_rounded, color: Colors.black),
            onPressed: _showFilterBottomSheet,
          ),
          const SizedBox(width: 8),
        ],
      ),
      body: SingleChildScrollView(
        physics: const BouncingScrollPhysics(),
        child: Padding(
          padding: const EdgeInsets.all(20.0),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Quick Filters
              SingleChildScrollView(
                scrollDirection: Axis.horizontal,
                physics: const BouncingScrollPhysics(),
                child: Row(
                  children: [
                    _buildFilterChip("All"),
                    _buildFilterChip("Lowest kcal", icon: Icons.bolt),
                    _buildFilterChip("High Protein", icon: Icons.trending_up),
                    _buildFilterChip("Low Fats", icon: Icons.opacity),
                    _buildFilterChip("Low Carbs", icon: Icons.restaurant),
                  ],
                ),
              ),
              const SizedBox(height: 25),
              
              if (_isLoading)
                const Center(
                  child: Padding(
                    padding: EdgeInsets.symmetric(vertical: 100),
                    child: CircularProgressIndicator(color: Color(0xfffc7e1a)),
                  ),
                )
              else if (_error != null)
                Center(
                  child: Padding(
                    padding: const EdgeInsets.symmetric(vertical: 80),
                    child: Column(
                      children: [
                        const Icon(Icons.error_outline, size: 50, color: Colors.redAccent),
                        const SizedBox(height: 15),
                        Text(_error!, style: const TextStyle(color: Colors.redAccent), textAlign: TextAlign.center),
                        const SizedBox(height: 15),
                        ElevatedButton(
                          onPressed: _fetchDbRecommendations,
                          style: ElevatedButton.styleFrom(backgroundColor: const Color(0xfffc7e1a)),
                          child: const Text("Retry", style: TextStyle(color: Colors.white)),
                        ),
                      ],
                    ),
                  ),
                )
              else ...[
                const Row(
                  children: [
                    Text(
                      "Best Match for You",
                      style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, letterSpacing: -0.5),
                    ),
                    Spacer(),
                    Icon(Icons.auto_awesome, color: Colors.amber, size: 18),
                  ],
                ),
                const SizedBox(height: 15),

                if (bestMatch != null)
                  _buildBestMatchCard(bestMatch, appState)
                else
                  const Card(
                    child: Padding(
                      padding: EdgeInsets.all(20),
                      child: Text("No best match available under this calorie budget."),
                    ),
                  ),

                const SizedBox(height: 30),
                
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text(
                      "Showing ${filteredFoods.length} results",
                      style: TextStyle(color: Colors.grey.shade600, fontWeight: FontWeight.w500),
                    ),
                    if (selectedFilter != "All" || sortBy != "Recommended" || maxCalories < 600)
                      TextButton(
                        onPressed: () {
                          setState(() {
                            selectedFilter = "All";
                            maxCalories = 600.0;
                            sortBy = "Recommended";
                          });
                          _fetchDbRecommendations();
                        },
                        child: const Text(
                          "Reset All",
                          style: TextStyle(color: Color(0xfffc7e1a), fontWeight: FontWeight.bold),
                        ),
                      ),
                  ],
                ),
                const SizedBox(height: 10),

                filteredFoods.isEmpty 
                  ? Center(
                      child: Padding(
                        padding: const EdgeInsets.symmetric(vertical: 50),
                        child: Column(
                          children: [
                            Icon(Icons.search_off, size: 50, color: Colors.grey.shade300),
                            const SizedBox(height: 15),
                            const Text("No meals found for these filters", style: TextStyle(color: Colors.grey)),
                          ],
                        ),
                      ),
                    )
                  : ListView.builder(
                      shrinkWrap: true,
                      physics: const NeverScrollableScrollPhysics(),
                      itemCount: filteredFoods.length,
                      itemBuilder: (context, index) {
                        return _buildRecommendationItem(filteredFoods[index], appState);
                      },
                    ),
              ],

              const SizedBox(height: 20),
              
              GestureDetector(
                onTap: () {
                  Navigator.push(
                    context,
                    MaterialPageRoute(builder: (context) => const ChatPage()),
                  );
                },
                child: _buildCustomRequestCard(),
              ),
              
              const SizedBox(height: 30),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildFilterChip(String label, {IconData? icon}) {
    bool isSelected = selectedFilter == label;
    return GestureDetector(
      onTap: () {
        if (selectedFilter == label) return;
        setState(() {
          selectedFilter = label;
        });
        _fetchDbRecommendations();
      },
      child: Container(
        margin: const EdgeInsets.only(right: 10),
        padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 12),
        decoration: BoxDecoration(
          color: isSelected ? const Color(0xFF1A1F26) : Colors.white,
          borderRadius: BorderRadius.circular(25),
          boxShadow: [
            if (!isSelected)
              BoxShadow(
                color: Colors.black.withOpacity(0.03),
                blurRadius: 10,
                offset: const Offset(0, 4),
              ),
          ],
          border: Border.all(color: isSelected ? Colors.transparent : Colors.grey.shade200),
        ),
        child: Row(
          children: [
            if (icon != null) ...[
              Icon(icon, color: isSelected ? Colors.greenAccent : Colors.green, size: 16),
              const SizedBox(width: 8),
            ],
            Text(
              label,
              style: TextStyle(
                color: isSelected ? Colors.white : Colors.black87,
                fontWeight: FontWeight.bold,
                fontSize: 13,
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildBestMatchCard(Product product, AppState appState) {
    return Container(
      width: double.infinity,
      decoration: BoxDecoration(
        color: const Color(0xFF1A1F26),
        borderRadius: BorderRadius.circular(25),
        boxShadow: [
          BoxShadow(color: Colors.black.withOpacity(0.2), blurRadius: 20, offset: const Offset(0, 10)),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Stack(
            children: [
              Container(
                height: 180,
                width: double.infinity,
                decoration: BoxDecoration(
                  borderRadius: const BorderRadius.vertical(top: Radius.circular(25)),
                  image: DecorationImage(image: NetworkImage(product.image), fit: BoxFit.cover),
                ),
              ),
              Positioned(
                top: 15,
                left: 15,
                child: Container(
                  padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                  decoration: BoxDecoration(color: Colors.green.withOpacity(0.9), borderRadius: BorderRadius.circular(10)),
                  child: const Row(
                    children: [
                      Icon(Icons.auto_awesome, color: Colors.white, size: 14),
                      SizedBox(width: 4),
                      Text("98% MATCH", style: TextStyle(color: Colors.white, fontSize: 10, fontWeight: FontWeight.bold)),
                    ],
                  ),
                ),
              ),
            ],
          ),
          Padding(
            padding: const EdgeInsets.all(20),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(product.name, style: const TextStyle(color: Colors.white, fontSize: 20, fontWeight: FontWeight.bold)),
                          const SizedBox(height: 4),
                          Text("Based on your goals", style: TextStyle(color: Colors.grey.shade500, fontSize: 12)),
                        ],
                      ),
                    ),
                    GestureDetector(
                      onTap: () {
                        appState.addToCart(product);
                        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text("${product.name} added"), behavior: SnackBarBehavior.floating, backgroundColor: Colors.green));
                      },
                      child: Container(
                        padding: const EdgeInsets.all(12),
                        decoration: BoxDecoration(color: const Color(0xfffc7e1a), borderRadius: BorderRadius.circular(15)),
                        child: const Icon(Icons.add_shopping_cart, color: Colors.white, size: 20),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 20),
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    _buildMacroInfo("KCAL", product.calories.toString(), Colors.white),
                    _buildMacroInfo("PROT", "${product.protein.toStringAsFixed(0)}g", Colors.white),
                    _buildMacroInfo("CARB", "${product.carbs.toStringAsFixed(0)}g", Colors.white),
                    _buildMacroInfo("FAT", "${product.fats.toStringAsFixed(0)}g", Colors.white),
                  ],
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildRecommendationItem(Product product, AppState appState) {
    return Container(
      padding: const EdgeInsets.all(12),
      margin: const EdgeInsets.only(bottom: 15),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(20),
        boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.04), blurRadius: 10, offset: const Offset(0, 4))],
      ),
      child: Row(
        children: [
          Container(
            width: 100,
            height: 100,
            decoration: BoxDecoration(
              borderRadius: BorderRadius.circular(15),
              image: DecorationImage(image: NetworkImage(product.image), fit: BoxFit.cover),
            ),
          ),
          const SizedBox(width: 15),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(product.name, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                Text("Light and healthy", style: TextStyle(color: Colors.grey.shade500, fontSize: 12)),
                const SizedBox(height: 12),
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    _buildMacroInfo("KCAL", product.calories.toString(), Colors.black87),
                    _buildMacroInfo("PROT", "${product.protein.toStringAsFixed(0)}g", Colors.black87),
                    _buildMacroInfo("CARB", "${product.carbs.toStringAsFixed(0)}g", Colors.black87),
                    _buildMacroInfo("FAT", "${product.fats.toStringAsFixed(0)}g", Colors.black87),
                  ],
                ),
              ],
            ),
          ),
          const SizedBox(width: 10),
          GestureDetector(
            onTap: () {
              appState.addToCart(product);
              ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text("${product.name} added"), behavior: SnackBarBehavior.floating, backgroundColor: Colors.green));
            },
            child: Container(
              padding: const EdgeInsets.all(10),
              decoration: BoxDecoration(color: const Color(0xfffc7e1a).withOpacity(0.1), borderRadius: BorderRadius.circular(12)),
              child: const Icon(Icons.add, color: Color(0xfffc7e1a), size: 22),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildMacroInfo(String label, String value, Color textColor) {
    return Column(
      children: [
        Text(label, style: TextStyle(fontSize: 10, color: textColor.withOpacity(0.5), fontWeight: FontWeight.bold)),
        Text(value, style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: textColor)),
      ],
    );
  }

  Widget _buildCustomRequestCard() {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(25),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(20),
        gradient: LinearGradient(colors: [Colors.green.shade50, Colors.white], begin: Alignment.topLeft, end: Alignment.bottomRight),
        border: Border.all(color: Colors.green.withOpacity(0.1), width: 1),
      ),
      child: const Column(
        children: [
          Icon(Icons.restaurant_menu, color: Colors.green, size: 30),
          SizedBox(height: 10),
          Text("Can't find what you're looking for?", textAlign: TextAlign.center, style: TextStyle(color: Colors.black54, fontSize: 14)),
          SizedBox(height: 5),
          Text("Talk to our Food Assistant", style: TextStyle(color: Color(0xfffc7e1a), fontWeight: FontWeight.bold, fontSize: 18)),
        ],
      ),
    );
  }
}
