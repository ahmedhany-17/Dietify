-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: May 28, 2026 at 12:31 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `healthyfood`
--

-- --------------------------------------------------------

--
-- Table structure for table `backup_codes`
--

CREATE TABLE `backup_codes` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `code_hash` varchar(255) NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `carts`
--

CREATE TABLE `carts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `carts`
--

INSERT INTO `carts` (`id`, `user_id`, `created_at`, `updated_at`) VALUES
(1, 2, '2026-02-27 22:28:12', '2026-02-27 22:28:12'),
(3, 8, '2026-04-23 13:52:16', '2026-04-23 13:52:16');

-- --------------------------------------------------------

--
-- Table structure for table `cart_items`
--

CREATE TABLE `cart_items` (
  `id` int(11) NOT NULL,
  `cart_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cart_items`
--

INSERT INTO `cart_items` (`id`, `cart_id`, `product_id`, `quantity`) VALUES
(19, 3, 8, 1);

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `user_id`, `message`, `created_at`) VALUES
(117, 2, 'sup man', '2026-04-16 20:54:08'),
(118, 2, 'What\'s up? 😊 You\'re looking to eat healthier in general, right? I\'d be happy to help you with some balanced meal ideas. How about some grilled chicken with roasted veggies and quinoa? Or a hearty bowl of lentil soup with whole grain bread? Which one sounds good to you?', '2026-04-16 20:54:09'),
(119, 2, 'make dite plan for me ', '2026-04-16 20:54:39'),
(120, 2, 'You want a structured meal plan, huh? Before I can do that, I need to know how many days you\'d like the plan to cover. Do you want a 3-day meal plan or a 7-day meal plan?\n\nAlso, let me know if you have any dietary restrictions or preferences (e.g. vegetarian, gluten-free, etc.) that I should keep in mind while creating the plan.', '2026-04-16 20:54:39'),
(121, 2, '3-day', '2026-04-16 20:54:50'),
(122, 2, 'I\'ll create a 3-day structured meal plan for you. Before I begin, I just need to confirm a few details.\n\nSince you didn\'t mention any dietary restrictions, I\'ll assume you\'re looking for a general meal plan. If you\'re vegetarian, gluten-free, or have any other specific needs, please let me know.\n\nAlso, would you like your meals to be relatively light and easy to prepare, or can you spend a bit more time in the kitchen cooking?\n\n### Meal Plan Options:\n\nA) Quick & Easy\nB) Cooking Time Doesn\'t Matter\n\nPlease respond with the letter of your preferred meal plan option!', '2026-04-16 20:54:50'),
(123, 2, '3-day', '2026-04-16 20:54:53'),
(124, 2, 'You didn\'t answer the question. Please respond with \"A)\" for Quick & Easy or \"B)\" for Cooking Time Doesn\'t Matter.', '2026-04-16 20:54:53'),
(125, 2, 'A', '2026-04-16 20:55:12'),
(126, 2, 'You\'ve chosen the Quick & Easy option.\n\nHere\'s a 3-day structured meal plan for you:\n\n**Day 1**\n\n* Breakfast: Overnight oats with banana and almond milk 🥗\n	+ 1/2 cup rolled oats, 1/2 cup almond milk, 1 mashed banana, 1 tablespoon honey\n* Lunch: Grilled chicken breast with mixed greens salad 🥦\n	+ 4 oz grilled chicken, 2 cups mixed greens, 1 cup cherry tomatoes, 1 tablespoon vinaigrette\n* Dinner: One-pot pasta with marinara sauce and vegetables 🍝\n	+ 1 cup pasta, 1 cup marinara sauce, 1 cup mixed vegetables (bell peppers, onions, mushrooms)\n\n**Day 2**\n\n* Breakfast: Scrambled eggs with whole wheat toast and avocado 🥚\n	+ 2 scrambled eggs, 1 slice whole wheat toast, 1/2 avocado\n* Lunch: Turkey and cheese sandwich on whole wheat bread with carrot sticks 🥗\n	+ 2 slices whole wheat bread, 2 oz turkey breast, 1 oz cheese, 1 cup carrot sticks\n* Dinner: Baked salmon with roasted broccoli and quinoa 🍗', '2026-04-16 20:55:13'),
(127, 2, '3-day', '2026-04-16 20:55:16'),
(128, 2, ' Meal Plan (Continued)\n\n**Day 3**\n\n* Breakfast: Yogurt parfait with granola and mixed berries 🍓\n	+ 6 oz yogurt, 2 tablespoons granola, 1 cup mixed berries\n* Lunch: Chicken Caesar salad 🥗\n	+ 4 oz grilled chicken, 2 cups romaine lettuce, 1 cup croutons, 1 tablespoon Caesar dressing\n* Dinner: Black bean and corn tacos with whole wheat tortillas 🌯\n	+ 1 cup cooked black beans, 1 cup frozen corn, 2 whole wheat tortillas, 1 cup shredded cheese\n\n### Nutrition Tip\nRemember to stay hydrated by drinking plenty of water throughout the day. Aim for at least 8 cups (64 oz) of water daily.\n\nPlease let me know if you have any questions or if you\'d like to make any changes to this meal plan!', '2026-04-16 20:55:17'),
(129, 2, 'what is your name', '2026-04-16 20:58:41'),
(130, 2, '?', '2026-04-16 20:58:41'),
(131, 2, 'you namme ?', '2026-04-16 20:58:47'),
(132, 2, ' I\'m Mr. Hamboula, your personalized food guru! I\'m here to help you with all your culinary needs and make healthy eating a breeze.', '2026-04-16 20:58:47'),
(133, 2, 'your name', '2026-04-20 19:40:17'),
(134, 2, ' is not Mr. Hamboula', '2026-04-20 19:40:18'),
(135, 2, 'did i ask you about your name ?', '2026-04-20 19:40:47'),
(136, 2, 'did i ask you about your name ?', '2026-04-20 19:40:50'),
(137, 2, 'name ?', '2026-04-20 19:40:54'),
(138, 2, 'hi', '2026-04-20 19:40:57'),
(139, 2, ', i\'m your friendly Healthy Food Assistant! 👋', '2026-04-20 19:40:57'),
(140, 2, 'name ?', '2026-04-20 19:41:00'),
(141, 2, 'name ?', '2026-04-20 19:41:03'),
(142, 2, ' it\'s a pleasure to meet you, but I don\'t have a personal name. I\'m here to help you with healthy food suggestions!', '2026-04-20 19:41:04'),
(143, 2, 'did i ask you about your name ?', '2026-04-20 19:41:06'),
(144, 2, 'did i ask you about your name ?', '2026-04-20 19:41:07'),
(145, 2, 'name', '2026-04-20 19:41:26'),
(146, 2, ' is still \'Healthy Food Assistant\'. I\'m here to help!', '2026-04-20 19:41:26'),
(147, 2, 'give me dite plan for 7 days', '2026-04-21 12:46:14'),
(148, 2, 'give me dite plan for 7 days', '2026-04-21 12:46:16'),
(149, 2, '\n\nTo create a personalized 7-day meal plan, I need to know a few details from you. \n\nDo you want a 3-day or 7-day meal plan?', '2026-04-21 12:46:16'),
(150, 2, '7-day', '2026-04-21 12:46:17'),
(151, 2, ' meal plan it is!\n\nBefore I generate the plan, I just need to confirm - you want a STRUCTURED MEAL PLAN, right? 📝\n\nAlso, just to remind you, since your DIET GOAL is weight loss, I\'ll focus on low-calorie, high-fiber, and protein-rich options.\n\nHere\'s your 7-day meal plan:\n\n### Day 1\n- **Breakfast**: Oatmeal with Banana and Eggs 🍳 (300 calories, 5g protein, 5g fiber)\n  * 1 cup cooked oatmeal\n  * 1 medium banana, sliced\n  * 2 eggs, poached\n- **Lunch**: Grilled Chicken Breast with Mixed Greens Salad 🥗 (350 calories, 35g protein, 5g fiber)\n  * 4 oz grilled chicken breast\n  * 2 cups mixed greens\n  * 1/2 cup cherry tomatoes, halved\n  * 1 tablespoon olive oil and lemon juice dressing\n- **Dinner**: Baked Salmon with Quinoa and Steamed Broccoli 🍗 (400 calories, 30g protein, 10g fiber)\n  * 4 oz baked salmon\n  * 1/2 cup', '2026-04-21 12:46:18'),
(152, 2, '7-day', '2026-04-21 12:46:24'),
(153, 2, ' meal plan: Day 2-7\n\n### Day 2\n- **Breakfast**: Greek Yogurt with Berries and Granola 🍿 (250 calories, 20g protein, 4g fiber)\n  * 6 oz Greek yogurt\n  * 1/2 cup mixed berries\n  * 2 tablespoons granola\n- **Lunch**: Turkey and Avocado Wrap 🥗 (400 calories, 25g protein, 10g fiber)\n  * 1 whole wheat tortilla\n  * 2 oz sliced turkey breast\n  * 1/2 avocado, sliced\n  * 1 cup mixed greens\n- **Dinner**: Chicken and Vegetable Stir-Fry with Brown Rice 🍚 (500 calories, 35g protein, 10g fiber)\n  * 4 oz cooked chicken breast\n  * 1 cup mixed vegetables (bell peppers, carrots, snap peas)\n  * 1/2 cup cooked brown rice\n\n### Day 3\n- **Breakfast**: Smoothie Bowl with Banana and Almond Milk 🍌 (300 calories, 15g protein, 6g fiber)\n  * 1 medium banana\n  * 1 cup almond milk\n  * 1', '2026-04-21 12:46:24'),
(154, 2, 'the rest', '2026-04-21 12:46:32'),
(155, 2, ' of the meal plan:\n\n### Day 3\n- **Breakfast**: Smoothie Bowl with Banana and Almond Milk 🍌 (300 calories, 15g protein, 6g fiber)\n  * 1 medium banana\n  * 1 cup almond milk\n  * 1 scoop vanilla protein powder\n  * 1 tablespoon almond butter\n  * 1 cup mixed berries\n- **Lunch**: Chicken Caesar Salad 🥗 (350 calories, 30g protein, 5g fiber)\n  * 4 oz grilled chicken breast\n  * 2 cups romaine lettuce\n  * 1/4 cup Caesar dressing\n  * 1 cup croutons (whole wheat)\n- **Dinner**: Baked Chicken Thigh with Roasted Vegetables and Quinoa 🍗 (450 calories, 30g protein, 10g fiber)\n  * 4 oz baked chicken thigh\n  * 1 cup mixed roasted vegetables (Brussels sprouts, sweet potatoes, carrots)\n  * 1/2 cup cooked quinoa\n\n### Day 4\n- **Breakfast**: Avocado Toast with Poached Eggs 🥚 (300 calories, 15g protein, 7g fiber)\n  *', '2026-04-21 12:46:33'),
(156, 2, 'give me meal plan for 3 days ', '2026-04-21 19:18:32'),
(157, 2, 'give me meal plan for 3 days ', '2026-04-21 19:18:48'),
(158, 2, ' 📝\n\nSince you\'re looking to lose weight, I\'ll focus on low-calorie, high-fiber, and protein-rich options. Here\'s a 3-day meal plan for you:\n\n### Day 1\n- **Breakfast**: Oatmeal with Banana and Eggs 🍳 (300 calories, 5g protein, 5g fiber)\n  * 1 cup cooked oatmeal\n  * 1 medium banana, sliced\n  * 2 eggs, poached\n- **Lunch**: Grilled Chicken Breast with Mixed Greens Salad 🥗 (350 calories, 35g protein, 5g fiber)\n  * 4 oz grilled chicken breast\n  * 2 cups mixed greens\n  * 1/2 cup cherry tomatoes, halved\n  * 1 tablespoon olive oil and lemon juice dressing\n- **Dinner**: Baked Salmon with Quinoa and Steamed Broccoli 🍗 (400 calories, 30g protein, 10g fiber)\n  * 4 oz baked salmon\n  * 1/2 cup cooked quinoa\n  * 1 cup steamed broccoli\n\n### Day 2\n- **Breakfast**: Greek Yogurt with Berries and Granola 🍿 (250 calories, 20g protein, 4g fiber)\n  * 6 oz Greek yogurt\n  * 1/2 cup mixed berries\n  * 2 tablespoons granola\n- **Lunch**: Turkey and Avocado Wrap 🥗 (400 calories, 25g protein, 10g fiber)\n  * 1 whole wheat tortilla\n  * 2 oz sliced turkey breast\n  * 1/2 avocado, sliced\n  * 1 cup mixed greens\n- **Dinner**: Chicken and Vegetable Stir-Fry with Brown Rice 🍚 (500 calories, 35g protein, 10g fiber)\n  * 4 oz cooked chicken breast\n  * 1 cup mixed vegetables (bell peppers, carrots, snap peas)\n  * 1/2 cup cooked brown rice\n\n### Day 3\n- **Breakfast**: Smoothie Bowl with Banana and Almond Milk 🍌 (300 calories, 15g protein, 6g fiber)\n  * 1 medium banana\n  * 1 cup almond milk\n  * 1 scoop vanilla protein powder\n  * 1 tablespoon almond butter\n  * 1 cup mixed berries\n- **Lunch**: Chicken Caesar Salad 🥗 (350 calories, 30g protein, 5g fiber)\n  * 4 oz grilled chicken breast\n  * 2 cups romaine lettuce\n  * 1/4 cup Caesar dressing\n  * 1 cup croutons (whole wheat)\n- **Dinner**: Baked Chicken Thigh with Roasted Vegetables and Quinoa 🍗 (450 calories, 30g protein, 10g fiber)\n  * 4 oz baked chicken thigh\n  * 1 cup mixed roasted vegetables (Brussels sprouts, sweet potatoes, carrots)\n  * 1/2 cup cooked quinoa\n\n### Nutrition Tip: \n- Focus on whole, unprocessed foods to ensure you\'re getting the nutrients your body needs.\n- Aim to drink at least 8 cups of water per day to stay hydrated.\n- Incorporate physical activity into your daily routine, such as a 30-minute walk or jog.\n\nRemember, this meal plan is just a starting point. Feel free to adjust the portion sizes and ingredients based on your personal preferences and dietary needs!', '2026-04-21 19:18:57'),
(159, 2, '3-day', '2026-04-21 19:19:17'),
(160, 2, ' meal plan complete! 🎉\n\nNow, don\'t forget to stay hydrated and get moving to support your weight loss journey! 💪\n\nIf you need any more meal plan suggestions or have questions, feel free to ask!', '2026-04-21 19:19:17'),
(161, 8, 'i want fava beans meal for my dite', '2026-04-23 13:53:03'),
(162, 8, 'Fava beans are a nutritious and delicious addition to a healthy diet! 🥗 Here\'s a detailed recipe to get you started:\n\n### Fava Bean and Spinach Stew with Garlic Bread\n\n#### Servings: 4-6\n\n#### Diet: General (balanced, nutritious, and varied)\n\n#### Ingredients:\n\n#### For the stew:\n- 1 cup dried fava beans (soaked overnight and drained)\n- 2 tablespoons olive oil\n- 1 onion, chopped\n- 3 cloves garlic, minced\n- 2 cups chopped fresh spinach\n- 1 teaspoon ground cumin\n- 1 teaspoon smoked paprika\n- Salt and pepper to taste\n- 4 cups vegetable broth (low-sodium)\n- 1 can (14.5 oz) diced tomatoes\n- 1/4 cup chopped fresh parsley\n\n#### For the garlic bread:\n- 1 loaf Italian bread (about 1 pound)\n- 3 cloves garlic, minced\n- 1/4 cup olive oil\n- 1/2 cup grated Parmesan cheese (optional)\n\n### Instructions\n\n1. **Prepare the fava beans**: Rinse the soaked fava beans and place them in a large pot. Cover them with water and bring to a boil. Reduce the heat and simmer for 45-60 minutes, or until the beans are tender.\n\n2. **Make the stew**: In a large skillet, heat the olive oil over medium heat. Add the chopped onion and cook until softened, about 5 minutes. Add the minced garlic and cook for another minute.\n\n3. **Add the spinach and spices**: Stir in the chopped spinach, cumin, smoked paprika, salt, and pepper. Cook until the spinach has wilted.\n\n4. **Combine the stew ingredients**: Add the cooked fava beans, vegetable broth, diced tomatoes, and chopped parsley to the skillet. Stir well to combine.\n\n5. **Simmer the stew**: Bring the stew to a boil, then reduce the heat and simmer for 20-25 minutes, or until the flavors have melded together.\n\n6. **Prepare the garlic bread**: Preheat the oven to 350°F (180°C). Slice the Italian bread into 1-inch thick slices. In a small bowl, mix together the minced garlic and olive oil. Brush the garlic mixture onto each bread slice.\n\n7. **Bake the garlic bread**: Place the bread slices on a baking sheet and bake for 10-12 minutes, or until lightly toasted. Sprinkle with Parmesan cheese (if using).\n\n8. **Serve**: Serve the fava bean stew hot, accompanied by slices of garlic bread.\n\nEnjoy your delicious and nutritious fava bean meal! 🥗', '2026-04-23 13:53:04'),
(163, 8, 'i want MEAL PLAN FOR THIS DAY', '2026-04-23 13:53:22'),
(164, 8, 'I\'d be happy to help you with a meal plan for the day. To make it more tailored to your needs, can you please tell me if you\'d like a:\n\n**A)** 3-day meal plan or **B)** 7-day meal plan?\n\nIf you don\'t have a specific duration in mind, I can suggest a 3-day meal plan to get you started.\n\nAlso, considering your **DIET GOAL** is **weight_loss**, I\'ll make sure to suggest meals that are low in calories, high in fiber, and rich in protein.\n\nPlease let me know your preference, and I\'ll create a personalized meal plan for you! 🥗', '2026-04-23 13:53:22'),
(165, 8, '3-day', '2026-04-23 13:53:24'),
(166, 8, 'Here\'s a 3-day meal plan tailored to your **weight_loss** goal. This meal plan includes low-calorie, high-fiber, and protein-rich options to help you stay on track. 🥗\n\n**Day 1**\n\n### Breakfast\n\n* **Greek Yogurt and Berries with Granola**: 250 calories, 5g fiber, 20g protein\n	+ 1 cup Greek yogurt\n	+ 1/2 cup mixed berries\n	+ 2 tablespoons granola\n\n### Lunch\n\n* **Grilled Chicken and Veggie Wrap**: 400 calories, 10g fiber, 35g protein\n	+ 1 whole wheat tortilla\n	+ 2 oz grilled chicken breast\n	+ 1 cup mixed greens\n	+ 1/2 cup sliced cucumber\n	+ 1/4 cup sliced bell peppers\n\n### Snack\n\n* **Apple Slices with Almond Butter**: 150 calories, 4g fiber, 4g protein\n	+ 1 medium apple, sliced\n	+ 2 tablespoons almond butter\n\n### Dinner\n\n* **Baked Salmon with Quinoa and Steamed Broccoli**: 500 calories, 10g fiber, 35g protein\n	+ 6 oz salmon fillet\n	+ 1/2 cup cooked quinoa\n	+ 1 cup steamed broccoli\n\n### Nutrition Tip: 💡\nIncorporate physical activity into your daily routine, such as a 30-minute brisk walk in the morning or a home workout in the evening.\n\n---\n\n**Day 2**\n\n### Breakfast\n\n* **Oatmeal with Banana and Nuts**: 300 calories, 6g fiber, 10g protein\n	+ 1 cup cooked oatmeal\n	+ 1 medium banana\n	+ 1 tablespoon chopped walnuts\n\n### Lunch\n\n* **Turkey and Avocado Salad**: 450 calories, 10g fiber, 25g protein\n	+ 2 cups mixed greens\n	+ 2 oz sliced turkey breast\n	+ 1/2 avocado, diced\n	+ 1 cup cherry tomatoes, halved\n\n### Snack\n\n* **Cottage Cheese and Cucumber Slices**: 150 calories, 5g fiber, 15g protein\n	+ 1/2 cup cottage cheese\n	+ 1/2 cup sliced cucumber\n\n### Dinner\n\n* **Grilled Chicken and Vegetable Skewers**: 400 calories, 8g fiber, 30g protein\n	+ 2 oz grilled chicken breast\n	+ 1 cup mixed vegetables (bell peppers, onions, mushrooms)\n	+ 1 whole wheat pita\n\n### Nutrition Tip: ⏰\nStay hydrated by drinking at least 8 cups of water throughout the day.\n\n---\n\n**Day 3**\n\n### Breakfast\n\n* **Scrambled Eggs with Spinach and Whole Wheat Toast**: 250 calories, 5g fiber, 20g protein\n	+ 2 scrambled eggs\n	+ 1 cup fresh spinach\n	+ 2 slices whole wheat toast\n\n### Lunch\n\n* **Chicken and Quinoa Bowl**: 500 calories, 10g fiber, 30g protein\n	+ 2 oz cooked chicken breast\n	+ 1 cup cooked quinoa\n	+ 1 cup steamed green beans\n\n### Snack\n\n* **Carrot Sticks with Hummus**: 100 calories, 6g fiber, 5g protein\n	+ 4 carrot sticks\n	+ 2 tablespoons hummus\n\n### Dinner\n\n* **Baked Chicken Breast with Roasted Vegetables**: 400 calories, 10g fiber, 35g protein\n	+ 6 oz baked chicken breast\n	+ 1 cup roasted mixed vegetables (Brussels sprouts, sweet potatoes, carrots)\n\n### Nutrition Tip: 🌞\nGet enough sleep each night to help your body recover from the day\'s activities and support weight loss.\n\nThis meal plan provides approximately 1500-1700 calories per day, with a balance of protein, healthy fats, and complex carbohydrates to support weight loss. Feel free to adjust the portion sizes and ingredients based on your personal preferences and dietary needs.', '2026-04-23 13:53:26'),
(167, 2, 'hi', '2026-04-25 08:43:09'),
(168, 2, 'Hello! How can I help you with healthy food suggestions or a meal plan today? 🥗', '2026-04-25 08:43:09');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','paid','shipped','delivered','cancelled','refunded') NOT NULL DEFAULT 'pending',
  `location_description` varchar(255) NOT NULL,
  `phone` varchar(30) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `total_amount`, `status`, `location_description`, `phone`, `created_at`) VALUES
(1, 2, 30.00, 'delivered', 'No Address Provided', '0000000000', '2026-02-28 09:24:56'),
(2, 2, 60.00, 'cancelled', 'No Address Provided', '0000000000', '2026-02-28 10:01:24'),
(12, 2, 210.00, 'delivered', 'No Address Provided', '0000000000', '2026-03-04 17:08:58'),
(13, 2, 300.00, 'delivered', 'No Address Provided', '0000000000', '2026-03-04 17:32:29'),
(16, 2, 200.00, 'delivered', 'No Address Provided', '0000000000', '2026-03-06 09:02:40'),
(17, 2, 200.00, 'delivered', 'No Address Provided', '0000000000', '2026-03-06 09:07:02'),
(18, 2, 200.00, 'delivered', 'No Address Provided', '0000000000', '2026-03-07 20:18:14'),
(20, 2, 200.00, 'cancelled', 'No Address Provided', '0000000000', '2026-03-10 11:25:12'),
(21, 2, 340.00, 'cancelled', 'No Address Provided', '0000000000', '2026-04-16 18:51:20');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_name` varchar(150) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `quantity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `price`, `quantity`) VALUES
(1, 1, 1, 'salad ', 30.00, 1),
(2, 2, 1, 'salad ', 30.00, 2),
(12, 12, 1, 'salad ', 30.00, 7),
(13, 13, 1, 'salad ', 30.00, 10),
(16, 16, 3, 'Crazy Chicken Sandwich', 200.00, 1),
(17, 17, 3, 'Crazy Chicken Sandwich', 200.00, 1),
(18, 18, 3, 'Crazy Chicken Sandwich', 200.00, 1),
(20, 20, 3, 'Crazy Chicken Sandwich', 200.00, 1),
(21, 21, 5, 'Tikka Masala Chicken', 340.00, 1);

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `method` enum('stripe','cod') NOT NULL,
  `status` enum('pending','completed','failed','refunded') NOT NULL DEFAULT 'pending',
  `transaction_id` varchar(150) DEFAULT NULL,
  `paid_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `order_id`, `method`, `status`, `transaction_id`, `paid_at`, `created_at`) VALUES
(1, 1, 'stripe', 'completed', 'cs_test_a1KRsOJ4oV1dMapSo026HH5YCMpBa3Qb8lDzkpWkiKODc3XrjqryD9WlIJ', '2026-02-28 09:24:56', '2026-02-28 09:24:56'),
(2, 2, 'stripe', 'failed', 'cs_test_a1hsmqNdGF6KFra1cZpn18yGT6bRZ3iO2XedwHIzPqCHqkl3iLqXmh4U4C', NULL, '2026-02-28 10:03:01'),
(3, 12, 'stripe', 'completed', 'cs_test_a1GB314UOXzo3VvFUrnNykyaKP6pmezyQkj59hkvyU6u9oye1jpmcr5w5S', '2026-03-04 17:09:23', '2026-03-04 17:09:23'),
(4, 13, 'stripe', 'completed', 'cs_test_a1KQk7cwK2sAaLHtqig5YAB0aSovLSPkvUSD8VZQ3kJBTzMjru26X16XG7', '2026-03-04 17:32:57', '2026-03-04 17:32:57'),
(5, 16, 'stripe', 'completed', 'cs_test_a19ZrtPKIQujE6BAfo8bt8wlscLA0s0olNdk0ItHGT0XPDW1kpEaoiTEJE', '2026-03-06 09:04:22', '2026-03-06 09:04:22'),
(6, 17, 'stripe', 'completed', 'cs_test_a12OEn8IbHlTM8J7NxLiTbZQ2L2nIM4WmsEFnguStIAj2GEhlXrtAm74EN', '2026-03-06 09:07:26', '2026-03-06 09:07:26'),
(7, 18, 'stripe', 'completed', 'cs_test_a1gJLlly2xP247NCU1ALSd4WR46Z8sM9aXMIB6mcVvNsvn85mHMFQkmjJm', '2026-03-07 20:18:49', '2026-03-07 20:18:49');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `image_path` varchar(255) DEFAULT NULL,
  `type` enum('food','drink') NOT NULL,
  `status` enum('active','archived') NOT NULL DEFAULT 'active',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `description`, `price`, `stock`, `image_path`, `type`, `status`, `created_at`, `updated_at`) VALUES
(1, 'salad ', 'salad low kcal', 35.00, 55, 'assets/images/841d05fa5ee60b73637cccdc1373760a.jpg', 'food', 'active', '2026-02-27 22:25:49', '2026-02-27 22:25:49'),
(3, 'Crazy Chicken Sandwich', '4 pieces, cream cheese, chicken, jalapeno, crushed bread, tortilla, eggs and black. Calories 316, fat 6, protein 40, carbs 22.4', 200.00, 97, 'assets/images/1772645704_e5045101d028d974.webp', 'food', 'active', '2026-03-04 17:35:04', '2026-03-04 17:35:04'),
(5, 'Tikka Masala Chicken', 'Grilled chicken breasts with Healthy tikka masala sauce. Served With White Rice & Veggies Kcals : 575 protein 62 carb 60. Fat 10', 340.00, 10, 'assets/images/1776364889_2f0e6c94a2c46e82.webp', 'food', 'active', '2026-04-16 18:41:29', '2026-04-16 18:41:29'),
(6, 'Chicken Curry sauce', 'Chicken with Curry Sauce Calories 167, Fat 4, Protein 25, Carb 7 -100 g', 225.00, 55, 'assets/images/1776365214_75275a266146e67c.webp', 'food', 'active', '2026-04-16 18:46:54', '2026-04-16 18:46:54'),
(7, 'Chicken with Buffalo sauce Burger', 'Breaded Chicken, Buffalo Sauce, Pickled and Cheese Calories 429, Protein 49.5, Fat 19.9 and Carb 13.7', 150.00, 69, 'assets/images/1776368179_787ab29e9e7497da.webp', 'food', 'active', '2026-04-16 19:36:19', '2026-04-16 19:36:19'),
(8, 'Banana walnut power shake', 'Calories: 625 kcal\r\nProtein: 29 g\r\nCarbohydrates: 63 g\r\nSugars: 37 g\r\n Fibers: 7g \r\nFat: 31 g', 100.00, 33, 'assets/images/1776714402_5102915060da6432.webp', 'drink', 'active', '2026-04-20 19:46:42', '2026-04-20 19:46:42'),
(9, 'Banana with Milk', '327 cal, with non dairy almond milk.', 85.00, 222, 'assets/images/1776717664_5a44a2d1361b5609.webp', 'drink', 'active', '2026-04-20 20:41:04', '2026-04-20 20:41:04');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL,
  `comment` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`id`, `user_id`, `product_id`, `rating`, `comment`, `created_at`) VALUES
(1, 2, 1, 5, 'good food', '2026-03-04 17:32:10'),
(2, 2, 3, 5, 'very good meal i love it is batter then kfc', '2026-03-17 13:29:38');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('customer','admin') NOT NULL DEFAULT 'customer',
  `twofa_method` enum('none','totp','email') NOT NULL DEFAULT 'none',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp(),
  `is_verified` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `twofa_method`, `created_at`, `updated_at`, `is_verified`) VALUES
(2, 'talaat', '224021@eru.edu.eg', '$2y$10$fthxAMPPg4kb9dJ3K6cgnueHdKc2wzET2x62kVSmQOK4iylfEDMvK', 'admin', 'email', '2026-02-27 18:05:06', '2026-02-27 18:05:06', 1),
(7, 'abdo', 'abodymody65@gmail.com', '$2y$10$zGd2z8imPaKbuYV0mPKJ1eXcFnbc4xGOycs69RVhPJajJONJEnV9C', 'customer', 'none', '2026-04-12 09:29:56', '2026-04-12 09:29:56', 1),
(8, 'bacavog352@poisonword.com', 'bacavog352@poisonword.com', '$2y$10$lgkUWtiTMnejqB7Y02EUFuDfjbfpLtmFiLYEc0mLiA0Z31tGswMta', 'customer', 'none', '2026-04-23 13:44:46', '2026-04-23 13:44:46', 1),
(9, 'timekey910@poisonword.com', 'timekey910@poisonword.com', '$2y$10$0UfVCrslzJCxAUaBHaeR8eLIJIprTRIe14sIYXgwwhqSu4ox8dy3a', 'customer', 'none', '2026-04-25 08:24:30', '2026-04-25 08:24:30', 1);

-- --------------------------------------------------------

--
-- Table structure for table `user_addresses`
--

CREATE TABLE `user_addresses` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `location_description` varchar(255) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `is_default` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_addresses`
--

INSERT INTO `user_addresses` (`id`, `user_id`, `location_description`, `phone`, `is_default`, `created_at`, `updated_at`) VALUES
(1, 2, 'madinity b100 street 10 buidling 111 apartment 20', '01066452325', 1, '2026-04-22 20:45:20', '2026-04-22 20:45:20');

-- --------------------------------------------------------

--
-- Table structure for table `user_profiles`
--

CREATE TABLE `user_profiles` (
  `user_id` int(11) NOT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `weight` decimal(5,2) DEFAULT NULL,
  `height` decimal(5,2) DEFAULT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_profiles`
--

INSERT INTO `user_profiles` (`user_id`, `avatar`, `age`, `weight`, `height`, `gender`, `phone`) VALUES
(2, 'assets/images/avatar_2_1776340436.jpg', 21, 90.00, 170.00, 'male', '01066452322'),
(7, 'assets/images/default_avatar.png', 27, 60.00, 185.00, 'male', '01056988987'),
(8, NULL, 21, 111.00, 170.00, 'male', '01066458610');

-- --------------------------------------------------------

--
-- Table structure for table `user_totp`
--

CREATE TABLE `user_totp` (
  `user_id` int(11) NOT NULL,
  `totp_secret` varchar(64) NOT NULL,
  `confirmed_at` datetime NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `backup_codes`
--
ALTER TABLE `backup_codes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `carts`
--
ALTER TABLE `carts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cart_id` (`cart_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `messages_user_fk` (`user_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_addresses`
--
ALTER TABLE `user_addresses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `user_profiles`
--
ALTER TABLE `user_profiles`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `user_totp`
--
ALTER TABLE `user_totp`
  ADD PRIMARY KEY (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `backup_codes`
--
ALTER TABLE `backup_codes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `carts`
--
ALTER TABLE `carts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=169;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `user_addresses`
--
ALTER TABLE `user_addresses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `backup_codes`
--
ALTER TABLE `backup_codes`
  ADD CONSTRAINT `backup_codes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `carts`
--
ALTER TABLE `carts`
  ADD CONSTRAINT `carts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD CONSTRAINT `cart_items_ibfk_1` FOREIGN KEY (`cart_id`) REFERENCES `carts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_order_fk` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_product_fk` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_order_fk` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_product_fk` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_addresses`
--
ALTER TABLE `user_addresses`
  ADD CONSTRAINT `user_addresses_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_profiles`
--
ALTER TABLE `user_profiles`
  ADD CONSTRAINT `user_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_totp`
--
ALTER TABLE `user_totp`
  ADD CONSTRAINT `user_totp_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
