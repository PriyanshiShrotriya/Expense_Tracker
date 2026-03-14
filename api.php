<?php
// Start session
session_start();

// Include database configuration
require_once 'config.php';

// Set response type to JSON
header('Content-Type: application/json');

// Check CSRF token for POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !validate_csrf_token($_POST['csrf_token'] ?? '')) {
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit();
}

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$user_id = intval($_SESSION['user_id']);
$action = isset($_POST['action']) ? $_POST['action'] : '';

// Route the request
switch ($action) {
    // ============ CATEGORIES ============
    case 'get_categories':
        getCategories($conn, $user_id);
        break;
    
    case 'add_category':
        addCategory($conn, $user_id);
        break;
    
    case 'update_category':
        updateCategory($conn, $user_id);
        break;
    
    case 'delete_category':
        deleteCategory($conn, $user_id);
        break;
    
    // ============ EXPENSES ============
    case 'get_expenses':
        getExpenses($conn, $user_id);
        break;
    
    case 'add_expense':
        addExpense($conn, $user_id);
        break;
    
    case 'update_expense':
        updateExpense($conn, $user_id);
        break;
    
    case 'delete_expense':
        deleteExpense($conn, $user_id);
        break;
    
    case 'get_expense_stats':
        getExpenseStats($conn, $user_id);
        break;
    
    // ============ BUDGETS ============
    case 'get_budgets':
        getBudgets($conn, $user_id);
        break;
    
    case 'set_budget':
        setBudget($conn, $user_id);
        break;
    
    case 'delete_budget':
        deleteBudget($conn, $user_id);
        break;
    
    // ============ BUDGET PREDICTION (KEY FEATURE) ============
    case 'get_prediction':
        getBudgetPrediction($conn, $user_id);
        break;
    
    case 'get_monthly_trend':
        getMonthlyTrend($conn, $user_id);
        break;
    
    // ============ DASHBOARD DATA ============
    case 'get_dashboard_data':
        getDashboardData($conn, $user_id);
        break;
    
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

// Close connection
$conn->close();

// ============ CATEGORY FUNCTIONS ============

function getCategories($conn, $user_id) {
    $query = "SELECT * FROM categories WHERE user_id = ? ORDER BY name";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
    
    echo json_encode(['success' => true, 'categories' => $categories]);
    $stmt->close();
}

function addCategory($conn, $user_id) {
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $icon = isset($_POST['icon']) ? $_POST['icon'] : '📦';
    $color = isset($_POST['color']) ? $_POST['color'] : '#667eea';
    
    if (empty($name)) {
        echo json_encode(['success' => false, 'message' => 'Category name is required']);
        return;
    }
    
    $query = "INSERT INTO categories (user_id, name, icon, color) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("isss", $user_id, $name, $icon, $color);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Category added successfully', 'id' => $stmt->insert_id]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add category']);
    }
    $stmt->close();
}

function updateCategory($conn, $user_id) {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $icon = isset($_POST['icon']) ? $_POST['icon'] : '📦';
    $color = isset($_POST['color']) ? $_POST['color'] : '#667eea';
    
    if (empty($name) || $id == 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid data']);
        return;
    }
    
    $query = "UPDATE categories SET name = ?, icon = ?, color = ? WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sssii", $name, $icon, $color, $id, $user_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Category updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update category']);
    }
    $stmt->close();
}

function deleteCategory($conn, $user_id) {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    
    if ($id == 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid category']);
        return;
    }
    
    // Check if category has expenses
    $check = "SELECT COUNT(*) as count FROM expenses WHERE category_id = ? AND user_id = ?";
    $stmt = $conn->prepare($check);
    $stmt->bind_param("ii", $id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if ($result['count'] > 0) {
        echo json_encode(['success' => false, 'message' => 'Cannot delete category with expenses']);
        $stmt->close();
        return;
    }
    
    $query = "DELETE FROM categories WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $id, $user_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Category deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete category']);
    }
    $stmt->close();
}

// ============ EXPENSE FUNCTIONS ============

function getExpenses($conn, $user_id) {
    $month = isset($_POST['month']) ? intval($_POST['month']) : date('n');
    $year = isset($_POST['year']) ? intval($_POST['year']) : date('Y');
    $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
    
    $query = "SELECT e.*, c.name as category_name, c.icon as category_icon, c.color as category_color 
              FROM expenses e 
              JOIN categories c ON e.category_id = c.id 
              WHERE e.user_id = ? AND MONTH(e.expense_date) = ? AND YEAR(e.expense_date) = ?";
    
    $params = [$user_id, $month, $year];
    $types = "iii";
    
    if ($category_id > 0) {
        $query .= " AND e.category_id = ?";
        $params[] = $category_id;
        $types .= "i";
    }
    
    $query .= " ORDER BY e.expense_date DESC, e.id DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $expenses = [];
    while ($row = $result->fetch_assoc()) {
        $expenses[] = $row;
    }
    
    echo json_encode(['success' => true, 'expenses' => $expenses]);
    $stmt->close();
}

function addExpense($conn, $user_id) {
    $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
    $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    $expense_date = isset($_POST['expense_date']) ? $_POST['expense_date'] : date('Y-m-d');
    
    // Enhanced validation
    if ($category_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Valid category is required']);
        return;
    }
    if ($amount < 0.01) {
        echo json_encode(['success' => false, 'message' => 'Amount must be at least $0.01']);
        return;
    }
    if (!DateTime::createFromFormat('Y-m-d', $expense_date)) {
        echo json_encode(['success' => false, 'message' => 'Invalid date format']);
        return;
    }
    
    // Verify category belongs to user
    $check = "SELECT id FROM categories WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($check);
    $stmt->bind_param("ii", $category_id, $user_id);
    $stmt->execute();
    if ($stmt->num_rows == 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid category']);
        $stmt->close();
        return;
    }
    $stmt->close();
    
    $query = "INSERT INTO expenses (user_id, category_id, amount, description, expense_date) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iidss", $user_id, $category_id, $amount, $description, $expense_date);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Expense added successfully', 'id' => $stmt->insert_id]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add expense: ' . $conn->error]);
    }
    $stmt->close();
}

function updateExpense($conn, $user_id) {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
    $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    $expense_date = isset($_POST['expense_date']) ? $_POST['expense_date'] : date('Y-m-d');
    
    // Enhanced validation
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid expense ID']);
        return;
    }
    if ($category_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Valid category is required']);
        return;
    }
    if ($amount < 0.01) {
        echo json_encode(['success' => false, 'message' => 'Amount must be at least $0.01']);
        return;
    }
    if (!DateTime::createFromFormat('Y-m-d', $expense_date)) {
        echo json_encode(['success' => false, 'message' => 'Invalid date format']);
        return;
    }
    
    // Verify category belongs to user
    $check = "SELECT id FROM categories WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($check);
    $stmt->bind_param("ii", $category_id, $user_id);
    $stmt->execute();
    if ($stmt->num_rows == 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid category']);
        $stmt->close();
        return;
    }
    $stmt->close();
    
    $query = "UPDATE expenses SET category_id = ?, amount = ?, description = ?, expense_date = ? WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("idssii", $category_id, $amount, $description, $expense_date, $id, $user_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Expense updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update expense: ' . $conn->error]);
    }
    $stmt->close();
}

function deleteExpense($conn, $user_id) {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    
    if ($id == 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid expense']);
        return;
    }
    
    $query = "DELETE FROM expenses WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $id, $user_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Expense deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete expense']);
    }
    $stmt->close();
}

function getExpenseStats($conn, $user_id) {
    $month = isset($_POST['month']) ? intval($_POST['month']) : date('n');
    $year = isset($_POST['year']) ? intval($_POST['year']) : date('Y');
    
    // Total expenses this month
    $query = "SELECT COALESCE(SUM(amount), 0) as total FROM expenses 
              WHERE user_id = ? AND MONTH(expense_date) = ? AND YEAR(expense_date) = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iii", $user_id, $month, $year);
    $stmt->execute();
    $total = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
    
    // Expenses by category
    $query = "SELECT c.id, c.name, c.icon, c.color, COALESCE(SUM(e.amount), 0) as total 
              FROM categories c 
              LEFT JOIN expenses e ON c.id = e.category_id AND e.user_id = ? 
              AND MONTH(e.expense_date) = ? AND YEAR(e.expense_date) = ?
              WHERE c.user_id = ?
              GROUP BY c.id ORDER BY total DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iiii", $user_id, $month, $year, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $by_category = [];
    while ($row = $result->fetch_assoc()) {
        $by_category[] = $row;
    }
    $stmt->close();
    
    // Last month comparison
    $last_month = $month == 1 ? 12 : $month - 1;
    $last_year = $month == 1 ? $year - 1 : $year;
    
    $query = "SELECT COALESCE(SUM(amount), 0) as total FROM expenses 
              WHERE user_id = ? AND MONTH(expense_date) = ? AND YEAR(expense_date) = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iii", $user_id, $last_month, $last_year);
    $stmt->execute();
    $last_month_total = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
    
    // Calculate percentage change
    $percent_change = 0;
    if ($last_month_total > 0) {
        $percent_change = round((($total - $last_month_total) / $last_month_total) * 100, 1);
    }
    
    echo json_encode([
        'success' => true,
        'stats' => [
            'total' => $total,
            'by_category' => $by_category,
            'last_month_total' => $last_month_total,
            'percent_change' => $percent_change
        ]
    ]);
}

// ============ BUDGET FUNCTIONS ============

function getBudgets($conn, $user_id) {
    $month = isset($_POST['month']) ? intval($_POST['month']) : date('n');
    $year = isset($_POST['year']) ? intval($_POST['year']) : date('Y');
    
    $query = "SELECT b.*, c.name as category_name, c.icon as category_icon, c.color as category_color,
              (SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE user_id = ? AND category_id = b.category_id 
               AND MONTH(expense_date) = ? AND YEAR(expense_date) = ?) as spent
              FROM budgets b
              JOIN categories c ON b.category_id = c.id
              WHERE b.user_id = ? AND b.month = ? AND b.year = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iiiiii", $user_id, $month, $year, $user_id, $month, $year);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $budgets = [];
    while ($row = $result->fetch_assoc()) {
        $budgets[] = $row;
    }
    
    echo json_encode(['success' => true, 'budgets' => $budgets]);
    $stmt->close();
}

function setBudget($conn, $user_id) {
    $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
    $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
    $month = isset($_POST['month']) ? intval($_POST['month']) : date('n');
    $year = isset($_POST['year']) ? intval($_POST['year']) : date('Y');
    
    if ($category_id == 0 || $amount == 0) {
        echo json_encode(['success' => false, 'message' => 'Category and amount are required']);
        return;
    }
    
    // Check if budget exists
    $check = "SELECT id FROM budgets WHERE user_id = ? AND category_id = ? AND month = ? AND year = ?";
    $stmt = $conn->prepare($check);
    $stmt->bind_param("iiii", $user_id, $category_id, $month, $year);
    $stmt->execute();
    $exists = $stmt->num_rows > 0;
    $stmt->close();
    
    if ($exists) {
        $query = "UPDATE budgets SET amount = ? WHERE user_id = ? AND category_id = ? AND month = ? AND year = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("diiii", $amount, $user_id, $category_id, $month, $year);
    } else {
        $query = "INSERT INTO budgets (user_id, category_id, amount, month, year) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("iiddi", $user_id, $category_id, $amount, $month, $year);
    }
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Budget set successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to set budget']);
    }
    $stmt->close();
}

function deleteBudget($conn, $user_id) {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    
    if ($id == 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid budget']);
        return;
    }
    
    $query = "DELETE FROM budgets WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $id, $user_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Budget deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete budget']);
    }
    $stmt->close();
}

// ============ BUDGET PREDICTION FUNCTIONS (KEY FEATURE) ============

function getBudgetPrediction($conn, $user_id) {
    // Get last 6 months of expense data
    $months_to_analyze = 6;
    $current_month = date('n');
    $current_year = date('Y');
    
    $predictions = [];
    $total_prediction = 0;
    
    // Get all categories for the user
    $cat_query = "SELECT id, name, icon, color FROM categories WHERE user_id = ?";
    $cat_stmt = $conn->prepare($cat_query);
    $cat_stmt->bind_param("i", $user_id);
    $cat_stmt->execute();
    $cat_result = $cat_stmt->get_result();
    
    while ($category = $cat_result->fetch_assoc()) {
        $category_id = $category['id'];
        $monthly_totals = [];
        
        // Get expenses for each of the last 6 months
        for ($i = 0; $i < $months_to_analyze; $i++) {
            $month = $current_month - $i;
            $year = $current_year;
            
            while ($month <= 0) {
                $month += 12;
                $year -= 1;
            }
            
            $query = "SELECT COALESCE(SUM(amount), 0) as total FROM expenses 
                      WHERE user_id = ? AND category_id = ? 
                      AND MONTH(expense_date) = ? AND YEAR(expense_date) = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("iiii", $user_id, $category_id, $month, $year);
            $stmt->execute();
            $total = $stmt->get_result()->fetch_assoc()['total'];
            $stmt->close();
            
            $monthly_totals[] = [
                'month' => $month,
                'year' => $year,
                'total' => floatval($total)
            ];
        }
        
        // Calculate prediction using weighted moving average
        $prediction = calculatePrediction($monthly_totals);
        
        // Get current budget for comparison
        $budget_query = "SELECT amount FROM budgets 
                         WHERE user_id = ? AND category_id = ? AND month = ? AND year = ?";
        $budget_stmt = $conn->prepare($budget_query);
        $next_month = $current_month + 1;
        $next_year = $current_year;
        if ($next_month > 12) {
            $next_month = 1;
            $next_year++;
        }
        $budget_stmt->bind_param("iiii", $user_id, $category_id, $next_month, $next_year);
        $budget_stmt->execute();
        $budget_result = $budget_stmt->get_result();
        $budget = 0;
        if ($budget_result->num_rows > 0) {
            $budget = floatval($budget_result->fetch_assoc()['amount']);
        }
        $budget_stmt->close();
        
        $predictions[] = [
            'category_id' => $category_id,
            'category_name' => $category['name'],
            'category_icon' => $category['icon'],
            'category_color' => $category['color'],
            'prediction' => round($prediction, 2),
            'budget' => $budget,
            'over_budget' => $budget > 0 && $prediction > $budget,
            'monthly_data' => array_reverse($monthly_totals),
            'trend' => calculateTrend($monthly_totals)
        ];
        
        $total_prediction += $prediction;
    }
    $cat_stmt->close();
    
    // Filter out categories with no prediction
    $predictions = array_filter($predictions, function($p) {
        return $p['prediction'] > 0;
    });
    $predictions = array_values($predictions);
    
    echo json_encode([
        'success' => true,
        'predictions' => $predictions,
        'total_prediction' => round($total_prediction, 2),
        'next_month' => date('F Y', strtotime('+1 month'))
    ]);
}

function calculatePrediction($monthly_totals) {
    if (count($monthly_totals) < 2) {
        return $monthly_totals[0]['total'] ?? 0;
    }
    
    // Weighted moving average (more recent months have higher weight)
    $weights = [0.1, 0.15, 0.2, 0.2, 0.15, 0.2]; // Weights for 6 months
    $weighted_sum = 0;
    $weight_total = 0;
    
    for ($i = 0; $i < count($monthly_totals); $i++) {
        $weight = $weights[$i] ?? 0.1;
        $weighted_sum += $monthly_totals[$i]['total'] * $weight;
        $weight_total += $weight;
    }
    
    $base_prediction = $weighted_sum / $weight_total;
    
    // Calculate trend factor
    $trend = calculateTrendFactor($monthly_totals);
    
    // Apply trend to prediction
    $final_prediction = $base_prediction * (1 + $trend);
    
    return max(0, $final_prediction);
}

function calculateTrendFactor($monthly_totals) {
    if (count($monthly_totals) < 2) {
        return 0;
    }
    
    // Simple linear regression for trend
    $n = count($monthly_totals);
    $sum_x = 0;
    $sum_y = 0;
    $sum_xy = 0;
    $sum_x2 = 0;
    
    for ($i = 0; $i < $n; $i++) {
        $x = $i;
        $y = $monthly_totals[$i]['total'];
        $sum_x += $x;
        $sum_y += $y;
        $sum_xy += $x * $y;
        $sum_x2 += $x * $x;
    }
    
    $slope = ($n * $sum_xy - $sum_x * $sum_y) / ($n * $sum_x2 - $sum_x * $sum_x);
    
    // Normalize slope by average
    $avg = $sum_y / $n;
    if ($avg > 0) {
        return $slope / $avg;
    }
    
    return 0;
}

function calculateTrend($monthly_totals) {
    $trend_factor = calculateTrendFactor($monthly_totals);
    
    if ($trend_factor > 0.1) {
        return 'increasing';
    } elseif ($trend_factor < -0.1) {
        return 'decreasing';
    } else {
        return 'stable';
    }
}

function getMonthlyTrend($conn, $user_id) {
    $months = 12;
    $current_month = date('n');
    $current_year = date('Y');
    
    $monthly_data = [];
    
    for ($i = $months - 1; $i >= 0; $i--) {
        $month = $current_month - $i;
        $year = $current_year;
        
        while ($month <= 0) {
            $month += 12;
            $year -= 1;
        }
        
        $query = "SELECT COALESCE(SUM(amount), 0) as total FROM expenses 
                  WHERE user_id = ? AND MONTH(expense_date) = ? AND YEAR(expense_date) = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("iii", $user_id, $month, $year);
        $stmt->execute();
        $total = floatval($stmt->get_result()->fetch_assoc()['total']);
        $stmt->close();
        
        $monthly_data[] = [
            'month' => $month,
            'year' => $year,
            'month_name' => date('M', mktime(0, 0, 0, $month, 1)),
            'total' => $total
        ];
    }
    
    echo json_encode(['success' => true, 'monthly_data' => $monthly_data]);
}

// ============ DASHBOARD DATA ============

function getDashboardData($conn, $user_id) {
    $month = isset($_POST['month']) ? intval($_POST['month']) : date('n');
    $year = isset($_POST['year']) ? intval($_POST['year']) : date('Y');
    
    // Total this month
    $query = "SELECT COALESCE(SUM(amount), 0) as total FROM expenses 
              WHERE user_id = ? AND MONTH(expense_date) = ? AND YEAR(expense_date) = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iii", $user_id, $month, $year);
    $stmt->execute();
    $total = floatval($stmt->get_result()->fetch_assoc()['total']);
    $stmt->close();
    
    // Total budget for month
    $query = "SELECT COALESCE(SUM(amount), 0) as total FROM budgets 
              WHERE user_id = ? AND month = ? AND year = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iii", $user_id, $month, $year);
    $stmt->execute();
    $total_budget = floatval($stmt->get_result()->fetch_assoc()['total']);
    $stmt->close();
    
    // Recent expenses
    $query = "SELECT e.*, c.name as category_name, c.icon as category_icon, c.color as category_color 
              FROM expenses e 
              JOIN categories c ON e.category_id = c.id 
              WHERE e.user_id = ? 
              ORDER BY e.expense_date DESC, e.id DESC 
              LIMIT 5";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $recent_expenses = [];
    while ($row = $result->fetch_assoc()) {
        $recent_expenses[] = $row;
    }
    $stmt->close();
    
    // Top categories this month
    $query = "SELECT c.id, c.name, c.icon, c.color, COALESCE(SUM(e.amount), 0) as total 
              FROM categories c 
              LEFT JOIN expenses e ON c.id = e.category_id AND e.user_id = ? 
              AND MONTH(e.expense_date) = ? AND YEAR(e.expense_date) = ?
              WHERE c.user_id = ?
              GROUP BY c.id ORDER BY total DESC LIMIT 3";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iiii", $user_id, $month, $year, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $top_categories = [];
    while ($row = $result->fetch_assoc()) {
        $top_categories[] = $row;
    }
    $stmt->close();
    
    echo json_encode([
        'success' => true,
        'data' => [
            'total_expenses' => $total,
            'total_budget' => $total_budget,
            'remaining_budget' => $total_budget - $total,
            'budget_used_percent' => $total_budget > 0 ? round(($total / $total_budget) * 100, 1) : 0,
            'recent_expenses' => $recent_expenses,
            'top_categories' => $top_categories
        ]
    ]);
}
?>

