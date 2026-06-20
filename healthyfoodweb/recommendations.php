<?php
require_once __DIR__ . '/config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

// Fetch user data from database
$userId = $_SESSION['user_id'];
$userData = $conn->query("
    SELECT u.*, p.gender, p.age, p.weight, p.height 
    FROM users u 
    LEFT JOIN user_profiles p ON u.id = p.user_id 
    WHERE u.id = $userId
")->fetch_assoc();

include __DIR__ . '/header.php';
?>

<style>
    .container {
        max-width: 1000px;
        margin: 50px auto;
        padding: 20px;
        font-family: sans-serif;
    }

    .card {
        background: white;
        padding: 40px;
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    }

    .header {
        text-align: center;
        margin-bottom: 30px;
    }

    .header h1 {
        color: #333;
        font-size: 2.5rem;
    }

    /* Input Form Styles */
    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 30px;
    }

    .form-item {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .form-item label {
        font-weight: bold;
        color: #444;
    }

    .form-item input,
    .form-item select {
        padding: 12px;
        border: 2px solid #eee;
        border-radius: 10px;
        font-size: 1rem;
    }

    .btn {
        grid-column: span 2;
        background: #ff6b35;
        color: white;
        padding: 15px;
        border: none;
        border-radius: 10px;
        font-size: 1.2rem;
        font-weight: bold;
        cursor: pointer;
    }

    .btn:hover {
        background: #e85a20;
    }

    /* Result Area Styles */
    #results {
        display: none;
        margin-top: 50px;
    }

    .main-result {
        text-align: center;
        background: #f9f9f9;
        padding: 30px;
        border-radius: 20px;
        margin-bottom: 30px;
        border: 2px dashed #ddd;
    }

    .tdee-val {
        font-size: 4rem;
        font-weight: 900;
        color: #2ecc71;
        margin: 10px 0;
    }

    .grid {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 20px;
    }

    .grid-item {
        padding: 25px;
        border-radius: 15px;
        text-align: center;
        border: 1px solid #eee;
        background: white;
    }

    .grid-item h3 {
        margin-bottom: 15px;
        color: #333;
    }

    .cals {
        font-size: 1.8rem;
        font-weight: bold;
        color: #ff6b35;
        margin-bottom: 15px;
    }

    .macros {
        font-size: 0.9rem;
        color: #666;
        border-top: 1px solid #eee;
        padding-top: 10px;
    }
</style>

<div class="container">
    <div class="card">
        <div class="header">
            <h1>TDEE Calculator</h1>
            <p>Calculate your daily calorie needs instantly</p>
        </div>

        <form onsubmit="event.preventDefault(); runCalculator();" class="form-grid">
            <div class="form-item">
                <label>Gender</label>
                <select id="gender">
                    <option value="male" <?php if ($userData['gender'] == 'male')
                        echo 'selected'; ?>>Male</option>
                    <option value="female" <?php if ($userData['gender'] == 'female')
                        echo 'selected'; ?>>Female</option>
                </select>
            </div>
            <div class="form-item">
                <label>Age</label>
                <input type="number" id="age" value="<?php echo $userData['age']; ?>" required>
            </div>
            <div class="form-item">
                <label>Weight (kg)</label>
                <input type="number" step="0.1" id="weight" value="<?php echo $userData['weight']; ?>" required>
            </div>
            <div class="form-item">
                <label>Height (cm)</label>
                <input type="number" id="height" value="<?php echo $userData['height']; ?>" required>
            </div>
            <div class="form-item" style="grid-column: span 2;">
                <label>Activity Level</label>
                <select id="activity">
                    <option value="1.2">Sedentary (No Exercise)</option>
                    <option value="1.375">Lightly Active (1-2 days/week)</option>
                    <option value="1.55">Moderately Active (3-5 days/week)</option>
                    <option value="1.725">Very Active (6-7 days/week)</option>
                    <option value="1.9">Extra Active (Hard labor job)</option>
                </select>
            </div>
            <button type="submit" class="btn">Calculate Now</button>
        </form>

        <div id="results">
            <div class="main-result">
                <h2>Your Maintenance Calories</h2>
                <div class="tdee-val" id="res-tdee">0</div>
                <p>Calories per day</p>
            </div>

            <div class="grid">
                <!-- Lose Weight -->
                <div class="grid-item" style="border-top: 5px solid #ffa502;">
                    <h3>Weight Loss</h3>
                    <div class="cals" id="res-lose">0</div>
                    <div class="macros" id="mac-lose"></div>
                </div>
                <!-- Maintenance -->
                <div class="grid-item" style="border-top: 5px solid #2ecc71;">
                    <h3>Maintenance</h3>
                    <div class="cals" id="res-main">0</div>
                    <div class="macros" id="mac-main"></div>
                </div>
                <!-- Gain Muscle -->
                <div class="grid-item" style="border-top: 5px solid #3742fa;">
                    <h3>Weight Gain</h3>
                    <div class="cals" id="res-gain">0</div>
                    <div class="macros" id="mac-gain"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function runCalculator() {
        // 1. Get Values
        var g = document.getElementById('gender').value;
        var a = parseInt(document.getElementById('age').value);
        var w = parseFloat(document.getElementById('weight').value);
        var h = parseFloat(document.getElementById('height').value);
        var act = parseFloat(document.getElementById('activity').value);

        // 2. Real Math Calculations
        // BMR (Mifflin-St Jeor)
        var bmr = (10 * w) + (6.25 * h) - (5 * a);
        if (g == 'male') { bmr = bmr + 5; } else { bmr = bmr - 161; }

        // TDEE
        var tdee = Math.round(bmr * act);
        var lose = tdee - 500;
        var gain = tdee + 500;

        // 3. Show Results in UI
        document.getElementById('results').style.display = 'block';
        document.getElementById('res-tdee').innerHTML = tdee;

        // Fill the grid
        document.getElementById('res-main').innerHTML = tdee;
        document.getElementById('res-lose').innerHTML = lose;
        document.getElementById('res-gain').innerHTML = gain;

        // Macros for each (30% Protein, 40% Carbs, 30% Fats)
        document.getElementById('mac-main').innerHTML = "P: " + Math.round((tdee * 0.3) / 4) + "g | C: " + Math.round((tdee * 0.4) / 4) + "g | F: " + Math.round((tdee * 0.3) / 9) + "g";
        document.getElementById('mac-lose').innerHTML = "P: " + Math.round((lose * 0.3) / 4) + "g | C: " + Math.round((lose * 0.4) / 4) + "g | F: " + Math.round((lose * 0.3) / 9) + "g";
        document.getElementById('mac-gain').innerHTML = "P: " + Math.round((gain * 0.3) / 4) + "g | C: " + Math.round((gain * 0.4) / 4) + "g | F: " + Math.round((gain * 0.3) / 9) + "g";

        // Scroll to results
        document.getElementById('results').scrollIntoView({ behavior: 'smooth' });
    }

    // Run immediately if we have data
    window.onload = function () {
        if (document.getElementById('weight').value > 0) {
            runCalculator();
        }
    };
</script>

</main>
</body>

</html>