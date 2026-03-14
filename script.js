// Expense Tracker - Main JavaScript File
// Handles all dashboard functionality, API calls, and UI interactions

// Global variables
let currentMonth = new Date().getMonth() + 1;
let currentYear = new Date().getFullYear();
let categories = [];
let expenses = [];
let budgets = [];
let predictions = [];
let categoryChart = null;
let trendChart = null;
let selectedIcon = '📦';

// Initialize the dashboard
document.addEventListener('DOMContentLoaded', function() {
    initDashboard();
});

// Initialize dashboard
async function initDashboard() {
    await loadCategories();
    await loadDashboardData();
    await loadPredictions();
    setupEventListeners();
    updateMonthDisplay();
}

// Setup all event listeners
function setupEventListeners() {
    // Month navigation
    document.getElementById('prev-month').addEventListener('click', () => navigateMonth(-1));
    document.getElementById('next-month').addEventListener('click', () => navigateMonth(1));
    
    // Navigation menu
    document.querySelectorAll('.nav-item').forEach(item => {
        item.addEventListener('click', (e) => {
            e.preventDefault();
            const view = item.dataset.view;
            switchView(view);
        });
    });
    
    // Add expense button
    document.getElementById('add-expense-btn').addEventListener('click', openExpenseModal);
    document.getElementById('close-expense-modal').addEventListener('click', closeExpenseModal);
    document.getElementById('expense-form').addEventListener('submit', handleExpenseSubmit);
    
    // Add category button
    document.getElementById('add-category-btn').addEventListener('click', openCategoryModal);
    document.getElementById('close-category-modal').addEventListener('click', closeCategoryModal);
    document.getElementById('category-form').addEventListener('submit', handleCategorySubmit);
    
    // Icon picker
    document.querySelectorAll('.icon-option').forEach(btn => {
        btn.addEventListener('click', () => selectIcon(btn));
    });
    
    // Add budget button
    document.getElementById('add-budget-btn').addEventListener('click', openBudgetModal);
    document.getElementById('close-budget-modal').addEventListener('click', closeBudgetModal);
    document.getElementById('budget-form').addEventListener('submit', handleBudgetSubmit);
    
    // Close modals on outside click
    window.addEventListener('click', (e) => {
        if (e.target.classList.contains('modal')) {
            e.target.style.display = 'none';
        }
    });
}

// Navigate between months
function navigateMonth(direction) {
    currentMonth += direction;
    if (currentMonth > 12) {
        currentMonth = 1;
        currentYear++;
    } else if (currentMonth < 1) {
        currentMonth = 12;
        currentYear--;
    }
    updateMonthDisplay();
    loadDashboardData();
    loadBudgets();
}

// Update month display
function updateMonthDisplay() {
    const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
                        'July', 'August', 'September', 'October', 'November', 'December'];
    document.getElementById('current-month').textContent = `${monthNames[currentMonth - 1]} ${currentYear}`;
}

// Switch between views
function switchView(viewName) {
    // Update nav
    document.querySelectorAll('.nav-item').forEach(item => {
        item.classList.toggle('active', item.dataset.view === viewName);
    });
    
    // Update view
    document.querySelectorAll('.view-section').forEach(view => {
        view.classList.add('hidden');
    });
    document.getElementById(`${viewName}-view`).classList.remove('hidden');
    
    // Load data for specific views
    if (viewName === 'expenses') loadExpenses();
    if (viewName === 'budgets') loadBudgets();
    if (viewName === 'prediction') loadPredictions();
    if (viewName === 'categories') loadCategoriesUI();
}

// ============ DATA LOADING FUNCTIONS ============

// Load categories
async function loadCategories() {
    try {
        const response = await apiCall('get_categories', {});
        if (response.success) {
            categories = response.categories;
            populateCategorySelects();
        }
    } catch (error) {
        showToast('Error loading categories', 'error');
    }
}

// Load dashboard data
async function loadDashboardData() {
    try {
        const response = await apiCall('get_dashboard_data', {
            month: currentMonth,
            year: currentYear
        });
        
        if (response.success) {
            const data = response.data;
            
            // Update stats
            document.getElementById('total-expenses').textContent = formatCurrency(data.total_expenses);
            document.getElementById('total-budget').textContent = formatCurrency(data.total_budget);
            document.getElementById('remaining-budget').textContent = formatCurrency(data.remaining_budget);
            
            const percentChange = data.budget_used_percent - 100;
            const percentEl = document.getElementById('percent-change');
            percentEl.textContent = `${percentChange >= 0 ? '+' : ''}${percentChange.toFixed(1)}%`;
            percentEl.style.color = percentChange > 0 ? '#f5576c' : '#43e97b';
            
            // Render recent expenses
            renderRecentExpenses(data.recent_expenses);
            
            // Render charts
            await renderCategoryChart();
            await renderTrendChart();
        }
    } catch (error) {
        showToast('Error loading dashboard data', 'error');
    }
}

// Load all expenses
async function loadExpenses() {
    try {
        const response = await apiCall('get_expenses', {
            month: currentMonth,
            year: currentYear
        });
        
        if (response.success) {
            expenses = response.expenses;
            renderAllExpenses();
        }
    } catch (error) {
        showToast('Error loading expenses', 'error');
    }
}

// Load budgets
async function loadBudgets() {
    try {
        const response = await apiCall('get_budgets', {
            month: currentMonth,
            year: currentYear
        });
        
        if (response.success) {
            budgets = response.budgets;
            renderBudgets();
        }
    } catch (error) {
        showToast('Error loading budgets', 'error');
    }
}

// Load predictions (KEY FEATURE)
async function loadPredictions() {
    try {
        const response = await apiCall('get_prediction', {});
        
        if (response.success) {
            predictions = response.predictions;
            renderPredictions(response);
        }
    } catch (error) {
        showToast('Error loading predictions', 'error');
    }
}

// Load monthly trend data
async function loadMonthlyTrend() {
    try {
        const response = await apiCall('get_monthly_trend', {});
        return response.monthly_data || [];
    } catch (error) {
        return [];
    }
}

// ============ RENDERING FUNCTIONS ============

// Render recent expenses
function renderRecentExpenses(expenses) {
    const container = document.getElementById('recent-expenses');
    
    if (!expenses || expenses.length === 0) {
        container.innerHTML = '<div class="empty-state">No expenses yet. Add your first expense!</div>';
        return;
    }
    
    container.innerHTML = expenses.map(expense => `
        <div class="recent-item">
            <div class="recent-icon" style="background: ${expense.category_color}20; color: ${expense.category_color}">
                ${expense.category_icon}
            </div>
            <div class="recent-details">
                <span class="recent-category">${expense.category_name}</span>
                <span class="recent-desc">${expense.description || 'No description'}</span>
            </div>
            <div class="recent-amount">${formatCurrency(expense.amount)}</div>
        </div>
    `).join('');
}

// Render all expenses
function renderAllExpenses() {
    const container = document.getElementById('all-expenses');
    
    if (!expenses || expenses.length === 0) {
        container.innerHTML = '<div class="empty-state">No expenses for this month</div>';
        return;
    }
    
    container.innerHTML = expenses.map(expense => `
        <div class="expense-item">
            <div class="expense-icon" style="background: ${expense.category_color}20; color: ${expense.category_color}">
                ${expense.category_icon}
            </div>
            <div class="expense-details">
                <span class="expense-category">${expense.category_name}</span>
                <span class="expense-desc">${expense.description || 'No description'}</span>
                <span class="expense-date">${formatDate(expense.expense_date)}</span>
            </div>
            <div class="expense-amount">${formatCurrency(expense.amount)}</div>
            <div class="expense-actions">
                <button class="btn-icon" onclick="editExpense(${expense.id})" title="Edit">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn-icon btn-delete" onclick="deleteExpense(${expense.id})" title="Delete">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    `).join('');
}

// Render categories
function renderCategoriesUI() {
    const container = document.getElementById('categories-list');
    
    if (!categories || categories.length === 0) {
        container.innerHTML = '<div class="empty-state">No categories yet</div>';
        return;
    }
    
    container.innerHTML = categories.map(cat => `
        <div class="category-card">
            <div class="category-icon" style="background: ${cat.color}20; color: ${cat.color}">
                ${cat.icon}
            </div>
            <span class="category-name">${cat.name}</span>
            <div class="category-actions">
                <button class="btn-icon" onclick="editCategory(${cat.id})" title="Edit">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn-icon btn-delete" onclick="deleteCategory(${cat.id})" title="Delete">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    `).join('');
}

// Load categories for UI
async function loadCategoriesUI() {
    await loadCategories();
    renderCategoriesUI();
}

// Render budgets
function renderBudgets() {
    const container = document.getElementById('budgets-list');
    
    if (!budgets || budgets.length === 0) {
        container.innerHTML = '<div class="empty-state">No budgets set for this month</div>';
        return;
    }
    
    container.innerHTML = budgets.map(budget => {
        const percent = budget.budget > 0 ? (budget.spent / budget.budget) * 100 : 0;
        const isOver = percent > 100;
        
        return `
            <div class="budget-item">
                <div class="budget-icon" style="background: ${budget.category_color}20; color: ${budget.category_color}">
                    ${budget.category_icon}
                </div>
                <div class="budget-details">
                    <span class="budget-category">${budget.category_name}</span>
                    <div class="budget-progress">
                        <div class="progress-bar">
                            <div class="progress-fill ${isOver ? 'over' : ''}" style="width: ${Math.min(percent, 100)}%"></div>
                        </div>
                        <span class="budget-percent">${percent.toFixed(0)}%</span>
                    </div>
                </div>
                <div class="budget-amounts">
                    <span class="spent">${formatCurrency(budget.spent)}</span>
                    <span class="total">/ ${formatCurrency(budget.amount)}</span>
                </div>
                <button class="btn-icon btn-delete" onclick="deleteBudget(${budget.id})" title="Delete">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        `;
    }).join('');
}

// Render predictions (KEY FEATURE)
function renderPredictions(response) {
    const container = document.getElementById('predictions-by-category');
    document.getElementById('total-prediction').textContent = formatCurrency(response.total_prediction);
    document.getElementById('prediction-month').textContent = response.next_month;
    
    if (!predictions || predictions.length === 0) {
        container.innerHTML = '<div class="empty-state">Not enough data for predictions yet. Keep adding expenses!</div>';
        document.getElementById('insights-list').innerHTML = '';
        return;
    }
    
    // Render predictions by category
    container.innerHTML = predictions.map(pred => {
        const trendIcon = pred.trend === 'increasing' ? '📈' : (pred.trend === 'decreasing' ? '📉' : '➡️');
        const trendColor = pred.trend === 'increasing' ? '#f5576c' : (pred.trend === 'decreasing' ? '#43e97b' : '#666');
        const overBudget = pred.over_budget;
        
        return `
            <div class="prediction-card ${overBudget ? 'over-budget' : ''}">
                <div class="prediction-category">
                    <span class="prediction-icon" style="background: ${pred.category_color}20; color: ${pred.category_color}">
                        ${pred.category_icon}
                    </span>
                    <span class="prediction-name">${pred.category_name}</span>
                    <span class="prediction-trend" style="color: ${trendColor}" title="${pred.trend}">
                        ${trendIcon}
                    </span>
                </div>
                <div class="prediction-value">
                    <span class="pred-amount">${formatCurrency(pred.prediction)}</span>
                    <span class="pred-label">predicted</span>
                </div>
                ${pred.budget > 0 ? `
                    <div class="prediction-budget">
                        <span>Budget: ${formatCurrency(pred.budget)}</span>
                        <span class="budget-diff ${overBudget ? 'negative' : 'positive'}">
                            ${overBudget ? '⚠️ Over' : '✓ Under'}
                        </span>
                    </div>
                ` : ''}
                <div class="prediction-chart">
                    <canvas id="pred-chart-${pred.category_id}" height="60"></canvas>
                </div>
            </div>
        `;
    }).join('');
    
    // Render mini charts for each prediction
    predictions.forEach(pred => {
        renderMiniChart(pred.category_id, pred.monthly_data);
    });
    
    // Generate insights
    generateInsights();
}

// Generate AI insights
function generateInsights() {
    const insights = [];
    
    predictions.forEach(pred => {
        // Check if over budget prediction
        if (pred.over_budget) {
            insights.push({
                type: 'warning',
                message: `Your predicted spending on <strong>${pred.category_name}</strong> (${formatCurrency(pred.prediction)}) exceeds your budget (${formatCurrency(pred.budget)}). Consider reducing expenses in this category.`
            });
        }
        
        // Check increasing trends
        if (pred.trend === 'increasing') {
            insights.push({
                type: 'info',
                message: `Your spending on <strong>${pred.category_name}</strong> is increasing. It went up from ${formatCurrency(pred.monthly_data[0]?.total || 0)} to ${formatCurrency(pred.monthly_data[pred.monthly_data.length - 1]?.total || 0)} over 6 months.`
            });
        }
        
        // Check for potential savings
        if (pred.trend === 'decreasing' && pred.budget > pred.prediction) {
            insights.push({
                type: 'success',
                message: `Great job! You're predicted to spend less on <strong>${pred.category_name}</strong> than your budget allows. Potential savings: ${formatCurrency(pred.budget - pred.prediction)}`
            });
        }
    });
    
    // Add general insight if no data
    if (insights.length === 0) {
        insights.push({
            type: 'info',
            message: 'Keep tracking your expenses to get personalized insights and predictions!'
        });
    }
    
    const container = document.getElementById('insights-list');
    container.innerHTML = insights.map(insight => `
        <div class="insight-item ${insight.type}">
            <i class="fas fa-${insight.type === 'warning' ? 'exclamation-triangle' : (insight.type === 'success' ? 'check-circle' : 'info-circle')}"></i>
            <span>${insight.message}</span>
        </div>
    `).join('');
}

// ============ CHART FUNCTIONS ============

// Render category pie chart
async function renderCategoryChart() {
    try {
        const response = await apiCall('get_expense_stats', {
            month: currentMonth,
            year: currentYear
        });
        
        if (!response.success) return;
        
        const stats = response.stats;
        const ctx = document.getElementById('category-chart').getContext('2d');
        
        // Destroy existing chart
        if (categoryChart) categoryChart.destroy();
        
        // Filter out zero values
        const data = stats.by_category.filter(c => c.total > 0);
        
        if (data.length === 0) {
            ctx.clearRect(0, 0, ctx.canvas.width, ctx.canvas.height);
            ctx.font = '14px Arial';
            ctx.fillStyle = '#666';
            ctx.textAlign = 'center';
            ctx.fillText('No expenses this month', ctx.canvas.width / 2, ctx.canvas.height / 2);
            return;
        }
        
        categoryChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: data.map(c => c.name),
                datasets: [{
                    data: data.map(c => c.total),
                    backgroundColor: data.map(c => c.color),
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            usePointStyle: true,
                            padding: 15
                        }
                    }
                }
            }
        });
    } catch (error) {
        console.error('Error rendering category chart:', error);
    }
}

// Render trend line chart
async function renderTrendChart() {
    try {
        const monthlyData = await loadMonthlyTrend();
        
        if (!monthlyData || monthlyData.length === 0) return;
        
        const ctx = document.getElementById('trend-chart').getContext('2d');
        
        // Destroy existing chart
        if (trendChart) trendChart.destroy();
        
        trendChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: monthlyData.map(d => d.month_name),
                datasets: [{
                    label: 'Expenses',
                    data: monthlyData.map(d => d.total),
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#667eea',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: value => '$' + value
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    } catch (error) {
        console.error('Error rendering trend chart:', error);
    }
}

// Render mini chart for prediction
function renderMiniChart(categoryId, monthlyData) {
    const canvas = document.getElementById(`pred-chart-${categoryId}`);
    if (!canvas) return;
    
    const ctx = canvas.getContext('2d');
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: monthlyData.map(d => d.month_name),
            datasets: [{
                data: monthlyData.map(d => d.total),
                borderColor: '#667eea',
                backgroundColor: 'transparent',
                borderWidth: 2,
                tension: 0.4,
                pointRadius: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: { display: false },
                y: { display: false }
            },
            plugins: {
                legend: { display: false }
            }
        }
    });
}

// ============ FORM HANDLERS ============

// Populate category selects
function populateCategorySelects() {
    const options = categories.map(cat => 
        `<option value="${cat.id}">${cat.name}</option>`
    ).join('');
    
    document.getElementById('expense-category').innerHTML = options;
    document.getElementById('budget-category').innerHTML = options;
}

// Open expense modal
function openExpenseModal(expense = null) {
    const modal = document.getElementById('expense-modal');
    const form = document.getElementById('expense-form');
    const title = document.getElementById('expense-modal-title');
    const submitText = document.getElementById('expense-submit-text');
    
    form.reset();
    document.getElementById('expense-date').value = new Date().toISOString().split('T')[0];
    
    if (expense) {
        title.textContent = 'Edit Expense';
        submitText.textContent = 'Update Expense';
        document.getElementById('expense-id').value = expense.id;
        document.getElementById('expense-category').value = expense.category_id;
        document.getElementById('expense-amount').value = expense.amount;
        document.getElementById('expense-description').value = expense.description || '';
        document.getElementById('expense-date').value = expense.expense_date;
    } else {
        title.textContent = 'Add Expense';
        submitText.textContent = 'Add Expense';
        document.getElementById('expense-id').value = '';
    }
    
    modal.style.display = 'block';
}

// Close expense modal
function closeExpenseModal() {
    document.getElementById('expense-modal').style.display = 'none';
}

// Validate expense form
function validateExpenseForm() {
    const category = document.getElementById('expense-category').value;
    const amount = parseFloat(document.getElementById('expense-amount').value);
    const date = document.getElementById('expense-date').value;
    
    if (!category) return 'Please select a category';
    if (isNaN(amount) || amount < 0.01) return 'Amount must be at least $0.01';
    if (!date) return 'Please select a date';
    
    return null;
}

// Handle expense submit
async function handleExpenseSubmit(e) {
    e.preventDefault();
    
    const error = validateExpenseForm();
    if (error) {
        showToast(error, 'error');
        return;
    }
    
    const expenseId = document.getElementById('expense-id').value;
    const action = expenseId ? 'update_expense' : 'add_expense';
    
    showLoading('expense-form', expenseId ? 'Updating...' : 'Adding...');
    
    const data = {
        id: expenseId,
        category_id: document.getElementById('expense-category').value,
        amount: document.getElementById('expense-amount').value,
        description: document.getElementById('expense-description').value,
        expense_date: document.getElementById('expense-date').value
    };
    
    try {
        const response = await apiCall(action, data);
        
        hideLoading('expense-form');
        
        if (response.success) {
            showToast(expenseId ? 'Expense updated!' : 'Expense added!', 'success');
            closeExpenseModal();
            loadDashboardData();
            if (!document.getElementById('expenses-view').classList.contains('hidden')) {
                loadExpenses();
            }
        } else {
            showToast(response.message || 'Error saving expense', 'error');
        }
    } catch (error) {
        hideLoading('expense-form');
        showToast('Network error. Please try again.', 'error');
        console.error('API Error:', error);
    }
}

// Edit expense
function editExpense(id) {
    const expense = expenses.find(e => e.id === id);
    if (expense) {
        openExpenseModal(expense);
    }
}

// Delete expense
async function deleteExpense(id) {
    if (!confirm('Are you sure you want to delete this expense?')) return;
    
    try {
        const response = await apiCall('delete_expense', { id: id });
        
        if (response.success) {
            showToast('Expense deleted!', 'success');
            loadDashboardData();
            loadExpenses();
        } else {
            showToast(response.message || 'Error', 'error');
        }
    } catch (error) {
        showToast('Error deleting expense', 'error');
    }
}

// Open category modal
function openCategoryModal(category = null) {
    const modal = document.getElementById('category-modal');
    const form = document.getElementById('category-form');
    const title = document.getElementById('category-modal-title');
    const submitText = document.getElementById('category-submit-text');
    
    form.reset();
    selectIcon(document.querySelector('.icon-option'));
    
    if (category) {
        title.textContent = 'Edit Category';
        submitText.textContent = 'Update Category';
        document.getElementById('category-id').value = category.id;
        document.getElementById('category-name').value = category.name;
        document.getElementById('category-icon').value = category.icon;
        document.getElementById('category-color').value = category.color;
        
        // Select the correct icon
        document.querySelectorAll('.icon-option').forEach(opt => {
            opt.classList.toggle('selected', opt.dataset.icon === category.icon);
        });
        selectedIcon = category.icon;
    } else {
        title.textContent = 'Add Category';
        submitText.textContent = 'Add Category';
        document.getElementById('category-id').value = '';
    }
    
    modal.style.display = 'block';
}

// Close category modal
function closeCategoryModal() {
    document.getElementById('category-modal').style.display = 'none';
}

// Select icon
function selectIcon(btn) {
    document.querySelectorAll('.icon-option').forEach(opt => opt.classList.remove('selected'));
    btn.classList.add('selected');
    selectedIcon = btn.dataset.icon;
    document.getElementById('category-icon').value = selectedIcon;
}

// Handle category submit
async function handleCategorySubmit(e) {
    e.preventDefault();
    
    const categoryId = document.getElementById('category-id').value;
    const action = categoryId ? 'update_category' : 'add_category';
    
    showLoading('category-form', categoryId ? 'Updating...' : 'Adding...');
    
    const data = {
        id: categoryId,
        name: document.getElementById('category-name').value.trim(),
        icon: selectedIcon,
        color: document.getElementById('category-color').value
    };
    
    if (!data.name) {
        hideLoading('category-form');
        showToast('Category name is required', 'error');
        return;
    }
    
    try {
        const response = await apiCall(action, data);
        
        hideLoading('category-form');
        
        if (response.success) {
            showToast(categoryId ? 'Category updated!' : 'Category added!', 'success');
            closeCategoryModal();
            await loadCategories();
            renderCategoriesUI();
        } else {
            showToast(response.message || 'Error saving category', 'error');
        }
    } catch (error) {
        hideLoading('category-form');
        showToast('Network error. Please try again.', 'error');
    }
}

// Edit category
function editCategory(id) {
    const category = categories.find(c => c.id === id);
    if (category) {
        openCategoryModal(category);
    }
}

// Delete category
async function deleteCategory(id) {
    if (!confirm('Are you sure you want to delete this category?')) return;
    
    try {
        const response = await apiCall('delete_category', { id: id });
        
        if (response.success) {
            showToast('Category deleted!', 'success');
            await loadCategories();
            renderCategoriesUI();
        } else {
            showToast(response.message || 'Error', 'error');
        }
    } catch (error) {
        showToast('Error deleting category', 'error');
    }
}

// Open budget modal
function openBudgetModal(budget = null) {
    const modal = document.getElementById('budget-modal');
    
    document.getElementById('budget-form').reset();
    
    if (budget) {
        document.getElementById('budget-id').value = budget.id;
        document.getElementById('budget-category').value = budget.category_id;
        document.getElementById('budget-amount').value = budget.amount;
    } else {
        document.getElementById('budget-id').value = '';
    }
    
    modal.style.display = 'block';
}

// Close budget modal
function closeBudgetModal() {
    document.getElementById('budget-modal').style.display = 'none';
}

// Handle budget submit
async function handleBudgetSubmit(e) {
    e.preventDefault();
    
    const category_id = document.getElementById('budget-category').value;
    const amount = parseFloat(document.getElementById('budget-amount').value);
    
    if (!category_id || amount < 0.01) {
        showToast('Valid category and amount (>= $0.01) required', 'error');
        return;
    }
    
    showLoading('budget-form', 'Saving...');
    
    const data = {
        id: document.getElementById('budget-id').value,
        category_id: category_id,
        amount: amount,
        month: currentMonth,
        year: currentYear
    };
    
    try {
        const response = await apiCall('set_budget', data);
        
        hideLoading('budget-form');
        
        if (response.success) {
            showToast('Budget set successfully!', 'success');
            closeBudgetModal();
            loadBudgets();
            loadDashboardData();
        } else {
            showToast(response.message || 'Error setting budget', 'error');
        }
    } catch (error) {
        hideLoading('budget-form');
        showToast('Network error. Please try again.', 'error');
    }
}

// Delete budget
async function deleteBudget(id) {
    if (!confirm('Are you sure you want to delete this budget?')) return;
    
    try {
        const response = await apiCall('delete_budget', { id: id });
        
        if (response.success) {
            showToast('Budget deleted!', 'success');
            loadBudgets();
            loadDashboardData();
        } else {
            showToast(response.message || 'Error', 'error');
        }
    } catch (error) {
        showToast('Error deleting budget', 'error');
    }
}

// ============ HELPER FUNCTIONS ============

// Get CSRF token from meta tag
function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.content : '';
}

// Show loading state on forms
function showLoading(formId, buttonText = 'Saving...') {
    const form = document.getElementById(formId);
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    
    submitBtn.disabled = true;
    submitBtn.innerHTML = `<span class="spinner"></span> ${buttonText}`;
    form.dataset.originalButton = originalText;
}

// Hide loading state
function hideLoading(formId) {
    const form = document.getElementById(formId);
    const submitBtn = form.querySelector('button[type="submit"]');
    
    submitBtn.disabled = false;
    submitBtn.innerHTML = form.dataset.originalButton || 'Save';
    delete form.dataset.originalButton;
}

// API call wrapper
async function apiCall(action, data) {
    const formData = new FormData();
    formData.append('action', action);
    formData.append('csrf_token', getCsrfToken());
    
    for (const key in data) {
        formData.append(key, data[key]);
    }
    
    const response = await fetch('api.php', {
        method: 'POST',
        body: formData
    });
    
    return await response.json();
}

// Format currency
function formatCurrency(amount) {
    return '$' + parseFloat(amount || 0).toFixed(2);
}

// Format date
function formatDate(dateStr) {
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

// Show toast notification
function showToast(message, type = 'info') {
    const toast = document.getElementById('toast');
    const toastMessage = document.getElementById('toast-message');
    
    toast.className = `toast ${type}`;
    toastMessage.textContent = message;
    toast.classList.add('show');
    
    setTimeout(() => {
        toast.classList.remove('show');
    }, 3000);
}

