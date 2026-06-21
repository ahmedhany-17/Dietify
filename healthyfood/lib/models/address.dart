class Address {
  final String id;
  final String type; // e.g. "Home", "Work"
  final String streetAddress;
  final String aptNumber;
  final String floor;
  final String phoneNumber;
  bool isSelected;

  Address({
    required this.id,
    required this.type,
    required this.streetAddress,
    required this.aptNumber,
    required this.floor,
    required this.phoneNumber,
    this.isSelected = false,
  });

  // Helper to get formatted display
  String get formattedAddress => "$streetAddress, Apt $aptNumber, Floor $floor";
}
