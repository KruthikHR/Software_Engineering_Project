<?php
require_once 'config/database.php';
$db = new Database();
redirectIfNotLoggedIn();

$route_id = $_GET['id'] ?? 0;
$error = '';
$success = '';

// Get route details
$stmt = $db->prepare("SELECT * FROM routes WHERE id = ? AND status = 'active'");
$stmt->bind_param("i", $route_id);
$stmt->execute();
$route = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$route) {
    header("Location: routes.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $seats = intval($_POST['seats']);
    $travel_date = $_POST['travel_date'];
    
    if ($seats < 1 || $seats > 5) {
        $error = "You can book 1 to 5 seats only";
    } elseif ($seats > $route['available_seats']) {
        $error = "Not enough seats available";
    } else {
        // Start transaction
        $db->conn->begin_transaction();
        
        try {
            // Create booking
            $total_price = $seats * $route['price'];
            $stmt = $db->prepare("INSERT INTO bookings (user_id, booking_type, route_id, seats_booked, total_price, travel_date) VALUES (?, 'transport', ?, ?, ?, ?)");
            $stmt->bind_param("iiids", $_SESSION['user_id'], $route_id, $seats, $total_price, $travel_date);
            $stmt->execute();
            
            // Update available seats
            $stmt = $db->prepare("UPDATE routes SET available_seats = available_seats - ? WHERE id = ?");
            $stmt->bind_param("ii", $seats, $route_id);
            $stmt->execute();
            
            $db->conn->commit();
            $success = "Booking successful! Booking ID: #" . $db->conn->insert_id;
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
    <title>Book Bus - BusBook Pro</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .booking-container {
            width: 100%;
            max-width: 800px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
        }

        .logo {
            text-align: center;
            font-size: 28px;
            font-weight: 700;
            color: #4f46e5;
            margin-bottom: 20px;
        }

        .page-title {
            text-align: center;
            color: #4f46e5;
            margin-bottom: 30px;
        }

        .alert {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }

        .alert-error {
            background: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }

        .alert-success {
            background: #efe;
            color: #3c3;
            border: 1px solid #cfc;
        }

        .booking-details {
            background: rgba(79, 70, 229, 0.05);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid rgba(79, 70, 229, 0.1);
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            color: #666;
            font-weight: 500;
        }

        .detail-value {
            color: #4f46e5;
            font-weight: 600;
        }

        .booking-form {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 30px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            color: #555;
            margin-bottom: 10px;
            font-weight: 500;
            font-size: 16px;
        }

        .form-control {
            width: 100%;
            padding: 15px;
            border: 2px solid #ddd;
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }

        .seats-selector {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .seat-option {
            padding: 12px 20px;
            border: 2px solid #ddd;
            border-radius: 10px;
            background: white;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            min-width: 80px;
        }

        .seat-option:hover {
            border-color: #4f46e5;
        }

        .seat-option.selected {
            background: #4f46e5;
            color: white;
            border-color: #4f46e5;
        }

        .price-summary {
            background: rgba(79, 70, 229, 0.1);
            border-radius: 12px;
            padding: 20px;
            margin: 25px 0;
        }

        .price-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .total-price {
            font-size: 24px;
            font-weight: 700;
            color: #4f46e5;
            border-top: 2px solid #4f46e5;
            padding-top: 15px;
            margin-top: 15px;
        }

        .btn-group {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }

        .btn {
            flex: 1;
            padding: 16px;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            text-align: center;
        }

        .btn-primary {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            color: white;
        }

        .btn-secondary {
            background: #f1f5f9;
            color: #4f46e5;
            border: 2px solid #4f46e5;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }

        @media (max-width: 768px) {
            .booking-container {
                padding: 25px 20px;
            }
            
            .btn-group {
                flex-direction: column;
            }
            
            .detail-row {
                flex-direction: column;
                gap: 5px;
            }
        }
    </style>
</head>
<body>
    <div class="booking-container">
        <div class="logo">🚌 BusBook Pro</div>
        <h1 class="page-title">Book Bus Ticket</h1>
        
        <?php if($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if($success): ?>
            <div class="alert alert-success"><?php echo $success; ?>
                <p style="margin-top: 10px;">
                    <a href="dashboard.php" class="btn" style="background: #10b981; margin: 5px;">View Dashboard</a>
                    <a href="routes.php" class="btn btn-secondary" style="margin: 5px;">Book Another</a>
                </p>
            </div>
        <?php else: ?>
        
        <div class="booking-details">
            <div class="detail-row">
                <span class="detail-label">Bus Name:</span>
                <span class="detail-value"><?php echo htmlspecialchars($route['bus_name']); ?> (<?php echo $route['bus_type']; ?>)</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Route:</span>
                <span class="detail-value"><?php echo htmlspecialchars($route['origin']); ?> → <?php echo htmlspecialchars($route['destination']); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Departure:</span>
                <span class="detail-value"><?php echo date('M d, Y h:i A', strtotime($route['departure_time'])); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Arrival:</span>
                <span class="detail-value"><?php echo date('M d, Y h:i A', strtotime($route['arrival_time'])); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Available Seats:</span>
                <span class="detail-value" style="color: <?php echo ($route['available_seats'] > 10) ? '#10b981' : '#f59e0b'; ?>">
                    <?php echo $route['available_seats']; ?>
                </span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Price per Seat:</span>
                <span class="detail-value">$<?php echo $route['price']; ?></span>
            </div>
        </div>

        <form method="POST" action="" class="booking-form">
            <div class="form-group">
                <label for="travel_date">Travel Date</label>
                <input type="date" id="travel_date" name="travel_date" class="form-control" 
                       min="<?php echo date('Y-m-d'); ?>" required>
            </div>

            <div class="form-group">
                <label>Number of Seats (Max 5)</label>
                <div class="seats-selector" id="seatsSelector">
                    <?php for($i = 1; $i <= min(5, $route['available_seats']); $i++): ?>
                        <div class="seat-option" data-seats="<?php echo $i; ?>">
                            <?php echo $i; ?> Seat<?php echo $i > 1 ? 's' : ''; ?>
                        </div>
                    <?php endfor; ?>
                </div>
                <input type="hidden" name="seats" id="selectedSeats" value="1" required>
            </div>

            <div class="price-summary">
                <div class="price-row">
                    <span>Price per seat:</span>
                    <span>$<span id="pricePerSeat"><?php echo $route['price']; ?></span></span>
                </div>
                <div class="price-row">
                    <span>Number of seats:</span>
                    <span><span id="displaySeats">1</span> seat(s)</span>
                </div>
                <div class="price-row total-price">
                    <span>Total Price:</span>
                    <span>$<span id="totalPrice"><?php echo $route['price']; ?></span></span>
                </div>
            </div>

            <div class="btn-group">
                <a href="routes.php" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Confirm Booking</button>
            </div>
        </form>
        <?php endif; ?>
    </div>

    <script>
        const pricePerSeat = <?php echo $route['price']; ?>;
        const seatOptions = document.querySelectorAll('.seat-option');
        const selectedSeatsInput = document.getElementById('selectedSeats');
        const displaySeats = document.getElementById('displaySeats');
        const totalPrice = document.getElementById('totalPrice');
        
        seatOptions.forEach(option => {
            option.addEventListener('click', function() {
                // Remove selected class from all options
                seatOptions.forEach(opt => opt.classList.remove('selected'));
                
                // Add selected class to clicked option
                this.classList.add('selected');
                
                // Update hidden input
                const seats = this.dataset.seats;
                selectedSeatsInput.value = seats;
                displaySeats.textContent = seats;
                
                // Calculate and update total price
                const total = pricePerSeat * parseInt(seats);
                totalPrice.textContent = total.toFixed(2);
            });
        });
        
        // Select first seat option by default
        if (seatOptions.length > 0) {
            seatOptions[0].classList.add('selected');
        }
        
        // Set minimum travel date to today
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('travel_date').min = today;
    </script>
</body>
</html>