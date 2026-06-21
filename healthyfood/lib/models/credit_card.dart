class CreditCard {
  final String id;
  final String cardNumber;
  final String cardHolderName;
  final String expiryDate;
  final String cvv;

  CreditCard({
    required this.id,
    required this.cardNumber,
    required this.cardHolderName,
    required this.expiryDate,
    required this.cvv,
  });

  String get last4 => cardNumber.length >= 4 ? cardNumber.substring(cardNumber.length - 4) : cardNumber;
}
