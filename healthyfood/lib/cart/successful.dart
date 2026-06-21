import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:healthyfood/home/home.dart';
import 'package:healthyfood/providers/app_state.dart';

class SuccessfulPage extends StatefulWidget {
  final String? orderId;
  const SuccessfulPage({super.key, this.orderId});

  @override
  State<SuccessfulPage> createState() => _SuccessfulPageState();
}

class _SuccessfulPageState extends State<SuccessfulPage> {
  bool _isCancelled = false;
  bool _isCancelling = false;

  Future<void> _handleCancelOrder() async {
    if (widget.orderId == null) return;
    
    setState(() {
      _isCancelling = true;
    });

    try {
      final appState = Provider.of<AppState>(context, listen: false);
      final res = await appState.cancelOrder(widget.orderId!);
      if (!mounted) return;
      if (res['success'] == true) {
        setState(() {
          _isCancelled = true;
        });
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Order cancelled successfully!'),
            backgroundColor: Colors.green,
            behavior: SnackBarBehavior.floating,
          ),
        );
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(res['error'] ?? 'Failed to cancel order'),
            backgroundColor: Colors.red,
            behavior: SnackBarBehavior.floating,
          ),
        );
      }
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Error: $e'),
          backgroundColor: Colors.red,
          behavior: SnackBarBehavior.floating,
        ),
      );
    } finally {
      if (mounted) {
        setState(() {
          _isCancelling = false;
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xfff8f9fa),
      body: Center(
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(24.0),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            crossAxisAlignment: CrossAxisAlignment.center,
            children: [
              // Beautiful Card
              Container(
                width: double.infinity,
                constraints: const BoxConstraints(maxWidth: 480),
                padding: const EdgeInsets.symmetric(vertical: 40.0, horizontal: 30.0),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(24.0),
                  boxShadow: [
                    BoxShadow(
                      color: Colors.black.withOpacity(0.04),
                      blurRadius: 20.0,
                      offset: const Offset(0, 10),
                    ),
                  ],
                ),
                child: Column(
                  children: [
                    // Status Icon
                    AnimatedSwitcher(
                      duration: const Duration(milliseconds: 300),
                      child: Icon(
                        _isCancelled ? Icons.cancel_outlined : Icons.check_circle_rounded,
                        key: ValueKey<bool>(_isCancelled),
                        color: _isCancelled ? Colors.red : const Color(0xfffc7e1a),
                        size: 90.0,
                      ),
                    ),
                    const SizedBox(height: 24.0),
                    
                    // Title
                    Text(
                      _isCancelled ? 'Order Cancelled' : 'Order Placed Successfully!',
                      style: const TextStyle(
                        fontSize: 24.0,
                        fontWeight: FontWeight.bold,
                        color: Color(0xff1e1e1e),
                      ),
                      textAlign: TextAlign.center,
                    ),
                    const SizedBox(height: 12.0),
                    
                    // Message
                    Text(
                      _isCancelled
                          ? 'Your order has been cancelled. Any payments will be refunded shortly.'
                          : 'Thank you for your order! Your delicious and healthy food is being prepared.',
                      style: const TextStyle(
                        fontSize: 16.0,
                        color: Color(0xff757575),
                        height: 1.5,
                      ),
                      textAlign: TextAlign.center,
                    ),
                    const SizedBox(height: 30.0),
                    
                    // Order ID display if present
                    if (widget.orderId != null) ...[
                      Container(
                        padding: const EdgeInsets.symmetric(vertical: 12.0, horizontal: 16.0),
                        decoration: BoxDecoration(
                          color: const Color(0xfff8f9fa),
                          borderRadius: BorderRadius.circular(12.0),
                          border: Border.all(color: const Color(0xffe9ecef)),
                        ),
                        child: Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            const Text(
                              'Order ID',
                              style: TextStyle(
                                fontSize: 14.0,
                                fontWeight: FontWeight.w500,
                                color: Color(0xff757575),
                              ),
                            ),
                            SelectableText(
                              widget.orderId!,
                              style: const TextStyle(
                                fontSize: 15.0,
                                fontWeight: FontWeight.bold,
                                color: Color(0xff1e1e1e),
                                fontFamily: 'monospace',
                              ),
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(height: 32.0),
                    ],
                    
                    // Cancel Order Button (Only shown if NOT already cancelled)
                    if (!_isCancelled && widget.orderId != null) ...[
                      SizedBox(
                        width: double.infinity,
                        height: 52.0,
                        child: OutlinedButton(
                          onPressed: _isCancelling ? null : _handleCancelOrder,
                          style: OutlinedButton.styleFrom(
                            side: const BorderSide(color: Colors.redAccent, width: 1.5),
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(14.0),
                            ),
                            foregroundColor: Colors.redAccent,
                          ),
                          child: _isCancelling
                              ? const Center(
                                  child: SizedBox(
                                    width: 24,
                                    height: 24,
                                    child: CircularProgressIndicator(
                                      strokeWidth: 2.5,
                                      valueColor: AlwaysStoppedAnimation<Color>(Colors.redAccent),
                                    ),
                                  ),
                                )
                              : const Text(
                                  'Cancel Order',
                                  style: TextStyle(
                                    fontSize: 16.0,
                                    fontWeight: FontWeight.bold,
                                  ),
                                ),
                        ),
                      ),
                      const SizedBox(height: 16.0),
                    ],
                    
                    // Back to Home Button
                    SizedBox(
                      width: double.infinity,
                      height: 52.0,
                      child: ElevatedButton(
                        onPressed: () {
                          Navigator.pushAndRemoveUntil(
                            context,
                            MaterialPageRoute(builder: (context) => const HomePage()),
                            (route) => false,
                          );
                        },
                        style: ElevatedButton.styleFrom(
                          backgroundColor: const Color(0xfffc7e1a),
                          foregroundColor: Colors.white,
                          elevation: 0,
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(14.0),
                          ),
                        ),
                        child: const Text(
                          'Back to Home',
                          style: TextStyle(
                            fontSize: 16.0,
                            fontWeight: FontWeight.bold,
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
      ),
    );
  }
}
