<?php
// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: index.html");
    exit();
}

// Include database configuration
require_once 'config.php';

// Get user info from session
$user_name = isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'User';
$user_email = isset($_SESSION['user_email']) ? htmlspecialchars($_SESSION['user_email']) : '';
$user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;

// Get current month and year for display
$current_month = date('n');
$current_year = date('Y');
$month_name = getMonthName($current_month);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo generate_csrf_token(); ?>">
    <title>Expense Tracker - Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <!-- Chart.js for analytics -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-brand">
            <span class="logo-icon">💰</span>
            <span class="logo-text">Expense Tracker</span>
        </div>
        <div class="nav-user">
            <span class="user-name"><?php echo $user_name; ?></span>
            <a href="logout.php" class="btn-logout">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </nav>

    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <ul class="nav-menu">
                <li class="nav-item active" data-view="dashboard">
                    <a href="#"><i class="fas fa-th-large"></i> Dashboard</a>
                </li>
                <li class="nav-item" data-view="expenses">
                    <a href="#"><i class="fas fa-receipt"></i> Expenses</a>
                </li>
                <li class="nav-item" data-view="categories">
                    <a href="#"><i class="fas fa-tags"></i> Categories</a>
                </li>
                <li class="nav-item" data-view="budgets">
                    <a href="#"><i class="fas fa-wallet"></i> Budgets</a>
                </li>
                <li class="nav-item" data-view="prediction">
                    <a href="#"><i class="fas fa-crystal-ball"></i> AI Prediction</a>
                </li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Header with month selector -->
            <div class="content-header">
                <div class="month-selector">
                    <button class="btn-nav" id="prev-month">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <span class="current-month" id="current-month"><?php echo $month_name . ' ' . $current_year; ?></span>
                    <button class="btn-nav" id="next-month">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
                <button class="btn-add" id="add-expense-btn">
                    <i class="fas fa-plus"></i> Add Expense
                </button>
            </div>

            <!-- Dashboard View -->
            <div class="view-section" id="dashboard-view">
                <!-- Stats Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #667eea, #764ba2);">
                            <i class="fas fa-wallet"></i>
                        </div>
                        <div class="stat-info">
                            <span class="stat-label">Total Expenses</span>
                            <span class="stat-value" id="total-expenses">$0.00</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #f093fb, #f5576c);">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="stat-info">
                            <span class="stat-label">vs Last Month</span>
                            <span class="stat-value" id="percent-change">0%</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #4facfe, #00f2fe);">
                            <i class="fas fa-piggy-bank"></i>
                        </div>
                        <div class="stat-info">
                            <span class="stat-label">Budget</span>
                            <span class="stat-value" id="total-budget">$0.00</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #43e97b, #38f9d7);">
                            <i class="fas fa-coins"></i>
                        </div>
                        <div class="stat-info">
                            <span class="stat-label">Remaining</span>
                            <span class="stat-value" id="remaining-budget">$0.00</span>
                        </div>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="charts-row">
                    <div class="chart-card">
                        <h3>Expenses by Category</h3>
                        <div class="chart-container">
                            <canvas id="category-chart"></canvas>
                        </div>
                    </div>
                    <div class="chart-card">
                        <h3>Monthly Trend</h3>
                        <div class="chart-container">
                            <canvas id="trend-chart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Recent Expenses -->
                <div class="recent-section">
                    <h3>Recent Expenses</h3>
                    <div class="recent-list" id="recent-expenses">
                        <!-- Populated by JavaScript -->
                    </div>
                </div>
            </div>

            <!-- Expenses View -->
            <div class="view-section hidden" id="expenses-view">
                <div class="section-header">
                    <h2>All Expenses</h2>
                </div>
                <div class="expenses-list" id="all-expenses">
                    <!-- Populated by JavaScript -->
                </div>
            </div>

            <!-- Categories View -->
            <div class="view-section hidden" id="categories-view">
                <div class="section-header">
                    <h2>Categories</h2>
                    <button class="btn-add" id="add-category-btn">
                        <i class="fas fa-plus"></i> Add Category
                    </button>
                </div>
                <div class="categories-grid" id="categories-list">
                    <!-- Populated by JavaScript -->
                </div>
            </div>

            <!-- Budgets View -->
            <div class="view-section hidden" id="budgets-view">
                <div class="section-header">
                    <h2>Monthly Budgets</h2>
                    <button class="btn-add" id="add-budget-btn">
                        <i class="fas fa-plus"></i> Set Budget
                    </button>
                </div>
                <div class="budgets-list" id="budgets-list">
                    <!-- Populated by JavaScript -->
                </div>
            </div>

            <!-- Budget Prediction View (KEY FEATURE) -->
            <div class="view-section hidden" id="prediction-view">
                <div class="section-header">
                    <h2>📊 Budget Prediction</h2>
                    <p class="section-desc">AI-powered expense forecasting based on your spending patterns</p>
                </div>
                
                <div class="prediction-summary">
                    <div class="prediction-card main-prediction">
                        <div class="prediction-header">
                            <i class="fas fa-crystal-ball"></i>
                            <span>Next Month Prediction</span>
                        </div>
                        <div class="prediction-month" id="prediction-month"><?php echo date('F Y', strtotime('+1 month')); ?></div>
                        <div class="prediction-amount" id="total-prediction">$0.00</div>
                        <p class="prediction-note">Based on your last 6 months spending patterns</p>
                    </div>
                </div>

                <div class="predictions-grid" id="predictions-by-category">
                    <!-- Populated by JavaScript -->
                </div>

                <div class="prediction-insights">
                    <h3><i class="fas fa-lightbulb"></i> Insights & Recommendations</h3>
                    <div class="insights-list" id="insights-list">
                        <!-- Populated by JavaScript -->
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Add Expense Modal -->
    <div class="modal" id="expense-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="expense-modal-title">Add Expense</h3>
                <button class="modal-close" id="close-expense-modal">&times;</button>
            </div>
            <form id="expense-form">
                <input type="hidden" id="expense-id">
                <div class="form-group">
                    <label for="expense-category">Category</label>
                    <select id="expense-category" required>
                        <!-- Populated by JavaScript -->
                    </select>
                </div>
                <div class="form-group">
                    <label for="expense-amount">Amount ($)</label>
                    <input type="number" id="expense-amount" step="0.01" min="0.01" required placeholder="0.00">
                </div>
                <div class="form-group">
                    <label for="expense-description">Description</label>
                    <input type="text" id="expense-description" placeholder="What was this expense for?">
                </div>
                <div class="form-group">
                    <label for="expense-date">Date</label>
                    <input type="date" id="expense-date" required>
                </div>
                <button type="submit" class="btn-primary btn-full">
                    <span id="expense-submit-text">Add Expense</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Add Category Modal -->
    <div class="modal" id="category-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="category-modal-title">Add Category</h3>
                <button class="modal-close" id="close-category-modal">&times;</button>
            </div>
            <form id="category-form">
                <input type="hidden" id="category-id">
                <div class="form-group">
                    <label for="category-name">Category Name</label>
                    <input type="text" id="category-name" required placeholder="e.g., Groceries">
                </div>
                <div class="form-group">
                    <label for="category-icon">Icon</label>
                    <div class="icon-picker" id="icon-picker">
                        <button type="button" class="icon-option selected" data-icon="📦">📦</button>
                        <button type="button" class="icon-option" data-icon="🍕">🍕</button>
                        <button type="button" class="icon-option" data-icon="🚗">🚗</button>
                        <button type="button" class="icon-option" data-icon="🛒">🛒</button>
                        <button type="button" class="icon-option" data-icon="🎬">🎬</button>
                        <button type="button" class="icon-option" data-icon="💡">💡</button>
                        <button type="button" class="icon-option" data-icon="🏥">🏥</button>
                        <button type="button" class="icon-option" data-icon="📚">📚</button>
                        <button type="button" class="icon-option" data-icon="✈️">✈️</button>
                        <button type="button" class="icon-option" data-icon="🎮">🎮</button>
                        <button type="button" class="icon-option" data-icon="👕">👕</button>
                        <button type="button" class="icon-option" data-icon="🏠">🏠</button>
                    </div>
                    <input type="hidden" id="category-icon" value="📦">
                </div>
                <div class="form-group">
                    <label for="category-color">Color</label>
                    <input type="color" id="category-color" value="#667eea">
                </div>
                <button type="submit" class="btn-primary btn-full">
                    <span id="category-submit-text">Add Category</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Set Budget Modal -->
    <div class="modal" id="budget-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Set Budget</h3>
                <button class="modal-close" id="close-budget-modal">&times;</button>
            </div>
            <form id="budget-form">
                <input type="hidden" id="budget-id">
                <div class="form-group">
                    <label for="budget-category">Category</label>
                    <select id="budget-category" required>
                        <!-- Populated by JavaScript -->
                    </select>
                </div>
                <div class="form-group">
                    <label for="budget-amount">Budget Amount ($)</label>
                    <input type="number" id="budget-amount" step="0.01" min="0.01" required placeholder="0.00">
                </div>
                <button type="submit" class="btn-primary btn-full">Set Budget</button>
            </form>
        </div>
    </div>

    <!-- Toast Notification -->
    <div class="toast" id="toast">
        <span class="toast-message" id="toast-message"></span>
    </div>

    <script src="script.js"></script>
</body>
</html>

