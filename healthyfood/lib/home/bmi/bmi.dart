import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:healthyfood/providers/app_state.dart';

class BMIScreen extends StatefulWidget {
  const BMIScreen({super.key});

  @override
  State<BMIScreen> createState() => _BMIScreenState();
}

class _BMIScreenState extends State<BMIScreen> {
  double height = 175;
  double weight = 72;

  double bmi = 23.5;
  String category = "HEALTHY";

  void calculateBMI() {
    double h = height / 100;
    double value = weight / (h * h);

    if (value.isNaN || value.isInfinite) return;

    setState(() {
      bmi = value;

      if (bmi < 18.5) {
        category = "UNDERWEIGHT";
      } else if (bmi < 25) {
        category = "HEALTHY";
      } else if (bmi < 30) {
        category = "OVERWEIGHT";
      } else {
        category = "OBESE";
      }
    });

    // Update global state
    Provider.of<AppState>(context, listen: false).updateProfile(
      height: height,
      weight: weight,
    );

    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(
        content: Text("BMI Recorded & Profile Updated!"),
        behavior: SnackBarBehavior.floating,
        backgroundColor: Colors.green,
      ),
    );
  }

  double getProgress() {
    return (bmi / 40).clamp(0.0, 1.0);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF5F7F5),
      appBar: AppBar(
        backgroundColor: const Color(0xfffc7e1a),
        foregroundColor: Colors.white,
        elevation: 0,
        title: const Text('BMI',
            style: TextStyle(fontWeight: FontWeight.bold)),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.symmetric(horizontal: 20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const SizedBox(height: 10),

            const Text("HEALTH METRICS",
                style: TextStyle(
                    color: Colors.green,
                    fontSize: 12,
                    fontWeight: FontWeight.bold)),

            const Text("BMI Calculator",
                style:
                TextStyle(fontSize: 32, fontWeight: FontWeight.bold)),

            const SizedBox(height: 8),

            const Text(
              "Understand your body mass index to better track your fitness journey.",
              style: TextStyle(color: Colors.grey),
            ),

            const SizedBox(height: 24),

            // HEIGHT
            buildSliderCard(
              "HEIGHT",
              height,
              "cm",
              Icons.straighten,
              100,
              220,
                  (val) => setState(() => height = val),
            ),

            const SizedBox(height: 16),

            // WEIGHT
            buildSliderCard(
              "WEIGHT",
              weight,
              "kg",
              Icons.monitor_weight,
              30,
              150,
                  (val) => setState(() => weight = val),
            ),

            const SizedBox(height: 24),

            // BUTTON
            ElevatedButton(
              onPressed: calculateBMI,
              style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xfffc7e1a),
                minimumSize: const Size(double.infinity, 60),
                shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(30)),
              ),
              child: const Text(
                "Calculate BMI",
                style: TextStyle(
                    color: Colors.white,
                    fontSize: 18,
                    fontWeight: FontWeight.bold),
              ),
            ),

            const SizedBox(height: 30),

            // RESULTS
            buildResultsCard(),

            const SizedBox(height: 20),

            // TIP
            buildTipCard(),

            const SizedBox(height: 40),
          ],
        ),
      ),
    );
  }

  Widget buildSliderCard(String label, double value, String unit,
      IconData icon, double min, double max, Function(double) onChanged) {
    double safeValue = value.clamp(min, max);

    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
          color: Colors.white, borderRadius: BorderRadius.circular(25)),
      child: Column(
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Icon(icon, color: Colors.green, size: 30),
              Column(
                crossAxisAlignment: CrossAxisAlignment.end,
                children: [
                  Text(label,
                      style: const TextStyle(
                          fontSize: 10,
                          color: Colors.grey,
                          fontWeight: FontWeight.bold)),
                  Row(
                    children: [
                      Text(
                        safeValue.toInt().toString(),
                        style: const TextStyle(
                            fontSize: 35, fontWeight: FontWeight.bold),
                      ),
                      const SizedBox(width: 8),
                      Text(unit,
                          style: const TextStyle(
                              fontSize: 18,
                              color: Colors.green,
                              fontWeight: FontWeight.w500)),
                    ],
                  )
                ],
              )
            ],
          ),
          Slider(
            value: safeValue,
            min: min,
            max: max,
            activeColor: const Color(0xfffc7e1a),
            onChanged: onChanged,
          ),
        ],
      ),
    );
  }

  Widget buildResultsCard() {
    return Container(
      padding: const EdgeInsets.all(24),
      decoration: BoxDecoration(
          color: Colors.white, borderRadius: BorderRadius.circular(30)),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              const Text("Your Results",
                  style: TextStyle(
                      color: Colors.grey, fontWeight: FontWeight.bold)),
              Container(
                padding:
                const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
                decoration: BoxDecoration(
                    color: Colors.green[100],
                    borderRadius: BorderRadius.circular(10)),
                child: Text(category,
                    style: const TextStyle(
                        color: Colors.green,
                        fontSize: 10,
                        fontWeight: FontWeight.bold)),
              )
            ],
          ),
          Row(
            children: [
              Text(bmi.toStringAsFixed(1),
                  style: const TextStyle(
                      fontSize: 60, fontWeight: FontWeight.bold)),
              const SizedBox(width: 10),
              const Text("BMI",
                  style: TextStyle(color: Colors.grey, fontSize: 20)),
            ],
          ),
          ClipRRect(
            borderRadius: BorderRadius.circular(10),
            child: LinearProgressIndicator(
              value: getProgress(),
              minHeight: 10,
              color: const Color(0xfffc7e1a),
              backgroundColor: const Color(0xFFEEEEEE),
            ),
          ),
          const SizedBox(height: 10),
          const Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text("UNDERWEIGHT",
                  style: TextStyle(fontSize: 8, color: Colors.grey)),
              Text("HEALTHY",
                  style: TextStyle(
                      fontSize: 8,
                      color: Colors.green,
                      fontWeight: FontWeight.bold)),
              Text("OVERWEIGHT",
                  style: TextStyle(fontSize: 8, color: Colors.grey)),
              Text("OBESE",
                  style: TextStyle(fontSize: 8, color: Colors.grey)),
            ],
          )
        ],
      ),
    );
  }

  Widget buildTipCard() {
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
          color: const Color(0xfffc7e1a),
          borderRadius: BorderRadius.circular(30)),
      child: const Row(
        children: [
          Icon(Icons.lightbulb, size: 40, color: Colors.white),
          SizedBox(width: 16),
          Expanded(
            child: Text(
              "Maintain a balanced diet and stay active to keep your BMI in a healthy range.",
              style: TextStyle(fontSize: 13, color: Colors.white),
            ),
          )
        ],
      ),
    );
  }
}