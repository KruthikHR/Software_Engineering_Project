<?php
require_once 'config/database.php';
$db = new Database();
redirectIfNotLoggedIn();

$tour_id = $_GET['id'] ?? 0;
$error = '';
$success = '';

// Get tour details
$stmt = $db->prepare("SELECT * FROM tours WHERE id = ? AND status = 'active'");
$stmt->bind_param("i", $tour_id);
$stmt->execute();
$tour = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$tour) {
    header("Location: tours.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $persons = intval($_POST['persons']);
    $travel_date = $_POST['travel_date'];
    
    if ($persons < 1 || $persons > 5) {
        $error = "You can book for 1 to 5 persons only";
    } elseif ($persons > $tour['available_slots']) {
        $error = "Not enough slots available";
    } else {
        // Start transaction
        $db->conn->begin_transaction();
        
        try {
            // Create booking
            $total_price = $persons * $tour['price'];
            $stmt = $db->prepare("INSERT INTO bookings (user_id, booking_type, tour_id, seats_booked, total_price, travel_date) VALUES (?, 'tour', ?, ?, ?, ?)");
            $stmt->bind_param("iiids", $_SESSION['user_id'], $tour_id, $persons, $total_price, $travel_date);
            $stmt->execute();
            
            // Update available slots
            $stmt = $db->prepare("UPDATE tours SET available_slots = available_slots - ? WHERE id = ?");
            $stmt->bind_param("ii", $persons, $tour_id);
            $stmt->execute();
            
            $db->conn->commit();
            $success = "Tour booking successful! Booking ID: #" . $db->conn->insert_id;
        } catch (Exception $e) {
            $db->conn->rollback();
            $error = "Booking failed. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Tour - BusBook Pro</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background: linear-gradient(135deg, #19348d 0%, #30156d 100%); min-height: 100vh; color: #333; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .booking-container { width: 100%; max-width: 800px; background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); border-radius: 20px; padding: 40px; box-shadow: 0 20px 40px rgba(0,0,0,0.2); }
        .logo { text-align: center; font-size: 28px; font-weight: 700; color: #0f766e; margin-bottom: 20px; }
        .page-title { text-align: center; color: #0f766e; margin-bottom: 30px; }
        .alert { padding: 15px; border-radius: 10px; margin-bottom: 20px; text-align: center; }
        .alert-error { background: #fee; color: #c33; border: 1px solid #fcc; }
        .alert-success { background: #efe; color: #3c3; border: 1px solid #cfc; }
        .booking-details { background: rgba(15, 118, 110, 0.05); border-radius: 15px; padding: 25px; margin-bottom: 30px; }
        .detail-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid rgba(15, 118, 110, 0.1); }
        .detail-row:last-child { border-bottom: none; }
        .detail-label { color: #666; font-weight: 500; }
        .detail-value { color: #0f766e; font-weight: 600; }
        .booking-form { background: #f8f9fa; border-radius: 15px; padding: 30px; }
        .form-group { margin-bottom: 25px; }
        .form-group label { display: block; color: #555; margin-bottom: 10px; font-weight: 500; font-size: 16px; }
        .form-control { width: 100%; padding: 15px; border: 2px solid #ddd; border-radius: 12px; font-size: 16px; transition: all 0.3s ease; }
        .form-control:focus { outline: none; border-color: #0f766e; box-shadow: 0 0 0 3px rgba(15, 118, 110, 0.1); }
        .persons-selector { display: flex; gap: 10px; flex-wrap: wrap; }
        .person-option { padding: 12px 20px; border: 2px solid #ddd; border-radius: 10px; background: white; cursor: pointer; transition: all 0.3s ease; text-align: center; min-width: 80px; }
        .person-option:hover { border-color: #0f766e; }
        .person-option.selected { background: #0f766e; color: white; border-color: #0f766e; }
        .price-summary { background: rgba(15, 118, 110, 0.1); border-radius: 12px; padding: 20px; margin: 25px 0; }
        .price-row { display: flex; justify-content: space-between; margin-bottom: 10px; }
        .total-price { font-size: 24px; font-weight: 700; color: #0f766e; border-top: 2px solid #0f766e; padding-top: 15px; margin-top: 15px; }
        .btn-group { display: flex; gap: 15px; margin-top: 30px; }
        .btn { flex: 1; padding: 16px; border: none; border-radius: 12px; font-size: 16px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; text-decoration: none; text-align: center; }
        .btn-primary { background: linear-gradient(135deg, #0f766e 0%, #115e59 100%); color: white; }
        .btn-secondary { background: #f1f5f9; color: #0f766e; border: 2px solid #0f766e; }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(0,0,0,0.2); }
        @media (max-width: 768px) {
            .booking-container { padding: 25px 20px; }
            .btn-group { flex-direction: column; }
            .detail-row { flex-direction: column; gap: 5px; }
        }
    </style>
</head>
<body>
    <div class="booking-container">
        <div class="logo">🏖️ BusBook Tours</div>
        <h1 class="page-title">Book Tour Package</h1>
        
        <?php if($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if($success): ?>
            <div class="alert alert-success"><?php echo $success; ?>
                <p style="margin-top: 10px;">
                    <a href="dashboard.php" class="btn" style="background: #10b981; margin: 5px;">View Dashboard</a>
                    <a href="tours.php" class="btn btn-secondary" style="margin: 5px;">Book Another</a>
                </p>
            </div>
        <?php else: ?>
        
        <div class="booking-details">
            <div class="detail-row">
                <span class="detail-label">Tour Name:</span>
                <span class="detail-value"><?php echo htmlspecialchars($tour['tour_name']); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Duration:</span>
                <span class="detail-value"><?php echo $tour['duration_days']; ?> Days</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Destinations:</span>
                <span class="detail-value"><?php echo htmlspecialchars($tour['destinations']); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Inclusions:</span>
                <span class="detail-value"><?php echo htmlspecialchars($tour['inclusions']); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Available Slots:</span>
                <span class="detail-value" style="color: <?php echo ($tour['available_slots'] > 5) ? '#10b981' : '#f59e0b'; ?>">
                    <?php echo $tour['available_slots']; ?>
                </span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Price per Person:</span>
                <span class="detail-value">$<?php echo $tour['price']; ?></span>
            </div>
        </div>

        <form method="POST" action="" class="booking-form">
            <div class="form-group">
                <label for="travel_date">Travel Date</label>
                <input type="date" id="travel_date" name="travel_date" class="form-control" 
                       min="<?php echo date('Y-m-d'); ?>" required>
            </div>

            <div class="form-group">
                <label>Number of Persons (Max 5)</label>
                <div class="persons-selector" id="personsSelector">
                    <?php for($i = 1; $i <= min(5, $tour['available_slots']); $i++): ?>
                        <div class="person-option" data-persons="<?php echo $i; ?>">
                            <?php echo $i; ?> Person<?php echo $i > 1 ? 's' : ''; ?>
                        </div>
                    <?php endfor; ?>
                </div>
                <input type="hidden" name="persons" id="selectedPersons" value="1" required>
            </div>

            <div class="price-summary">
                <div class="price-row">
                    <span>Price per person:</span>
                    <span>$<span id="pricePerPerson"><?php echo $tour['price']; ?></span></span>
                </div>
                <div class="price-row">
                    <span>Number of persons:</span>
                    <span><span id="displayPersons">1</span> person(s)</span>
                </div>
                <div class="price-row total-price">
                    <span>Total Price:</span>
                    <span>$<span id="totalPrice"><?php echo $tour['price']; ?></span></span>
                </div>
            </div>

            <div class="btn-group">
                <a href="tours.php" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Confirm Booking</button>
            </div>
        </form>
        <?php endif; ?>
    </div>

    <script>
        const pricePerPerson = <?php echo $tour['price']; ?>;
        const personOptions = document.querySelectorAll('.person-option');
        const selectedPersonsInput = document.getElementById('selectedPersons');
        const displayPersons = document.getElementById('displayPersons');
        const totalPrice = document.getElementById('totalPrice');
        
        personOptions.forEach(option => {
            option.addEventListener('click', function() {
                // Remove selected class from all options
                personOptions.forEach(opt => opt.classList.remove('selected'));
                
                // Add selected class to clicked option
                this.classList.add('selected');
                
                // Update hidden input
                const persons = this.dataset.persons;
                selectedPersonsInput.value = persons;
                displayPersons.textContent = persons;
                
                // Calculate and update total price
                const total = pricePerPerson * parseInt(persons);
                totalPrice.textContent = total.toFixed(2);
            });
        });
        
        // Select first option by default
        if (personOptions.length > 0) {
            personOptions[0].classList.add('selected');
        }
        
        // Set minimum travel date to today
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('travel_date').min = today;
    </script>
</body>
</html>