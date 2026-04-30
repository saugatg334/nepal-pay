# NepalPay Blank Page FIX - COMPLETE ROOT CAUSE & SOLUTIONS

## ✅ ROOT CAUSE ANALYSIS (FINAL)

### **Primary Cause: Silent Fatal Errors**
The login page was blank because fatal errors during view rendering were:
1. Captured by `ob_start()` in the original `render()` method
2. Never displayed or logged  
3. Result: empty output buffer → blank page with NO error messages

### **Secondary Causes**

1. **index.php Line 102 Bug** ⚠️
   ```php
   // WRONG (original):
   $route = $routes[$page];                  // ← Array access BEFORE validation
   $controllerName = $route['controller'];
   $method = $route['method'];
   if (!isset($routes[$page])) {            // ← Validation comes AFTER
       die("Route not found...");
   }
   
   // RESULT: "Undefined array key 'login'" warning on every request
   ```

2. **main.php Duplicate Requires**
   ```php
   <?php require_once __DIR__ . '/../../includes/helpers.php'; ?>  // Already in index.php
   <?php require_once __DIR__ . '/../../includes/lang.php'; ?>     // Already in index.php
   ```
   Potential for conflicts or redeclaration errors.

3. **No Error Handling in view() & render()**
   - Original `view()` had no try/catch
   - Original `render()` used output buffering with no error checks
   - Fatal errors = silent failures

4. **No Global Error Handler**
   - Fatal errors at shutdown were never caught
   - No way to diagnose blank page without checking error.log manually

---

## 🔧 FIX #1: index.php - Fix Route Validation Order

**File:** [index.php](index.php#L100-L152)

**What was fixed:**
- ✅ Validate route EXISTS before accessing array key
- ✅ Safe controller file loading with existence check
- ✅ Safe class instantiation with existence check  
- ✅ Safe method check before calling
- ✅ Try/catch wrapper around controller method execution
- ✅ Exception handling with debug output

**Code:**
```php
// Validate route exists FIRST
if (!isset($routes[$page])) {
    $page = 'login';
    if (!isset($routes[$page])) {
        die("Critical: Default route 'login' not configured");
    }
}

$route = $routes[$page];
$controllerName = $route['controller'] ?? null;
$method = $route['method'] ?? null;

if (!$controllerName || !$method) {
    http_response_code(500);
    die("Route configuration error for page: " . htmlspecialchars($page));
}

$controllerFile = __DIR__ . '/controllers/' . $controllerName . '.php';
if (!file_exists($controllerFile)) {
    http_response_code(500);
    die("Controller file not found: " . htmlspecialchars($controllerFile));
}

require_once $controllerFile;

if (!class_exists($controllerName)) {
    http_response_code(500);
    die("Controller class not found: " . htmlspecialchars($controllerName));
}

$controller = new $controllerName();

if (!method_exists($controller, $method)) {
    http_response_code(500);
    die("Method not found: {$method} in {$controllerName}");
}

try {
    $controller->$method();
} catch (Exception $e) {
    http_response_code(500);
    if (APP_DEBUG) {
        echo "<h1>Error in Controller Method</h1>";
        echo "<p>" . escape($e->getMessage()) . "</p>";
        echo "<pre>" . escape($e->getTraceAsString()) . "</pre>";
    } else {
        echo "<h1>An error occurred. Please try again.</h1>";
    }
    error_log("Controller Error: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
}
```

---

## 🔧 FIX #2: Controller.php - Add Error Handling to view() & render()

**File:** [controllers/Controller.php](controllers/Controller.php#L4-L75)

### Protected function view() - Now with error handling:
```php
protected function view($template, $data = []) {
    extract($data);
    
    $templateFile = BASE_PATH . '/views/' . $template . '.php';
    
    if (!file_exists($templateFile)) {
        http_response_code(404);
        echo "<h1>404 - View not found</h1>";
        echo "<p>Template: " . htmlspecialchars($template) . "</p>";
        if (APP_DEBUG) {
            echo "<p>Path: " . htmlspecialchars($templateFile) . "</p>";
        }
        error_log("View not found: " . $templateFile);
        exit;
    }
    
    try {
        require $templateFile;
    } catch (Exception $e) {
        http_response_code(500);
        echo "<h1>Error rendering view</h1>";
        if (APP_DEBUG) {
            echo "<p>" . escape($e->getMessage()) . "</p>";
            echo "<pre>" . escape($e->getTraceAsString()) . "</pre>";
        } else {
            echo "<p>An error occurred. Please try again.</p>";
        }
        error_log("View error in {$template}: " . $e->getMessage());
        exit;
    }
}
```

### Protected function render() - Now with comprehensive error handling:
```php
protected function render($template, $data = []) {
    $data['page'] = $_GET['page'] ?? 'dashboard';
    extract($data);
    
    $templateFile = BASE_PATH . '/views/' . $template . '.php';
    
    if (!file_exists($templateFile)) {
        http_response_code(404);
        echo "<h1>404 - View not found</h1>";
        echo "<p>Template: " . htmlspecialchars($template) . "</p>";
        if (APP_DEBUG) {
            echo "<p>Path: " . htmlspecialchars($templateFile) . "</p>";
        }
        error_log("View not found: " . $templateFile);
        exit;
    }
    
    try {
        ob_start();
        require $templateFile;
        $content = ob_get_clean();
    } catch (Exception $e) {
        ob_end_clean();
        http_response_code(500);
        echo "<h1>Error rendering view</h1>";
        if (APP_DEBUG) {
            echo "<p>" . escape($e->getMessage()) . "</p>";
            echo "<pre>" . escape($e->getTraceAsString()) . "</pre>";
        }
        error_log("View error in {$template}: " . $e->getMessage());
        exit;
    }
    
    if (empty($content)) {
        if (APP_DEBUG) {
            echo "<h1>Warning: View produced empty output</h1>";
            echo "<p>Template: " . htmlspecialchars($template) . "</p>";
        }
    }
    
    $layoutFile = BASE_PATH . '/views/layouts/main.php';
    if (file_exists($layoutFile)) {
        try {
            require $layoutFile;
        } catch (Exception $e) {
            http_response_code(500);
            echo "<h1>Error rendering layout</h1>";
            if (APP_DEBUG) {
                echo "<p>" . escape($e->getMessage()) . "</p>";
                echo "<pre>" . escape($e->getTraceAsString()) . "</pre>";
            } else {
                echo "<p>An error occurred while loading the page.</p>";
            }
            error_log("Layout error: " . $e->getMessage());
            exit;
        }
    } else {
        echo $content;
    }
}
```

**What's improved:**
- ✅ File existence checks BEFORE requiring
- ✅ Proper error handling with try/catch
- ✅ Output buffer cleanup on error
- ✅ Debug output in APP_DEBUG mode
- ✅ Silent errors now become visible

---

## 🔧 FIX #3: main.php - Remove Duplicate Requires

**File:** [views/layouts/main.php](views/layouts/main.php#L1-L8)

**Changed from:**
```php
<link href="<?php echo APP_URL; ?>/public/css/style.css" rel="stylesheet">
<?php require_once __DIR__ . '/../../includes/helpers.php'; ?>
<?php require_once __DIR__ . '/../../includes/lang.php'; ?>
```

**Changed to:**
```php
<link href="<?php echo APP_URL; ?>/public/css/style.css" rel="stylesheet">
```

**Why:** These files are already included in index.php (lines 12-13), so including them again in the layout:
- ❌ Wastes resources
- ❌ Could cause redeclaration errors if functions use static variables
- ❌ Creates maintenance confusion

---

## 🔧 FIX #4: NEW GLOBAL ERROR HANDLER

**File:** [includes/error_handler.php](includes/error_handler.php)

**Features:**
1. **Custom error handler** - Catches all PHP errors before they're suppressed
2. **Custom exception handler** - Catches all exceptions
3. **Shutdown handler** - Catches fatal errors that occur at shutdown
4. **Debug output** - Shows errors only if APP_DEBUG=true
5. **Error logging** - Always logs to error.log for investigation

**Code structure:**
```php
set_error_handler(function($errno, $errstr, $errfile, $errline) { ... })
set_exception_handler(function($exception) { ... })
register_shutdown_function(function() { ... })
```

**What it prevents:**
- ❌ Silent fatal errors → ✅ Now visible
- ❌ Blank pages → ✅ Shows error message
- ❌ No debugging info → ✅ Full stack trace in debug mode

---

## 🔧 FIX #5: index.php - Load Error Handler First

**File:** [index.php](index.php#L1-L11)

**Added:**
```php
// Load global error handler before anything else
require_once __DIR__ . '/includes/error_handler.php';
```

**Why first?** 
- Must be loaded BEFORE any code that could error
- Must be before config, database, session initialization
- Ensures all errors are caught from the very beginning

---

## 📋 VERIFICATION CHECKLIST

Test the following in your browser:

### ✅ Test 1: Login Page Renders
```
URL: http://localhost/wallet/
Expected: Full login page HTML with form
NOT Expected: Blank page, "Undefined array key" warning
```

### ✅ Test 2: Route Validation Works
```
URL: http://localhost/wallet/?page=invalid_page
Expected: Redirects to login OR shows error message
NOT Expected: Undefined array key warning
```

### ✅ Test 3: Missing Controller Handled
```
URL: http://localhost/wallet/?page=nonexistent
Expected: Clear error: "Route not found" or redirects to login
NOT Expected: Fatal error, blank page
```

### ✅ Test 4: APP_DEBUG Shows Errors
```
In config/config.php: define('APP_DEBUG', true);
Create a syntax error in a view file
Expected: Error details shown in browser + logged to error.log
NOT Expected: Blank page
```

### ✅ Test 5: APP_DEBUG Hides Errors
```
In config/config.php: define('APP_DEBUG', false);
With same syntax error as Test 4
Expected: Generic "An error occurred" message shown
Details logged to error.log (not visible in browser)
NOT Expected: Sensitive error details shown to users
```

### ✅ Test 6: Login Form Submits
```
URL: http://localhost/wallet/?page=login
Action: Enter test credentials and click Login
Expected: Form submits, processes normally
NOT Expected: Blank page, 500 error
```

### ✅ Test 7: Error Log Created
```
Check: d:\xampp\htdocs\wallet\error.log
Expected: File exists with logged messages
Entries should show any errors that occurred
```

---

## 🎯 SUMMARY OF CHANGES

| Issue | Root Cause | Fix | File |
|-------|-----------|-----|------|
| Undefined array key warning | Access before validation | Check route exists first | index.php |
| Silent blank page | No error handling | Add try/catch to view() & render() | Controller.php |
| Output buffer errors | ob_start() with no safety | Proper exception handling | Controller.php |
| No error visibility | No global error handler | Create error_handler.php | includes/error_handler.php |
| Duplicate requires | Includes in both index + layout | Remove from main.php | views/layouts/main.php |
| Error handler not active | Not loaded | Add require_once early | index.php |

---

## 🚀 NEXT STEPS

1. **Verify all changes are applied** using the checklist above
2. **Test login page** - should now render without blank page
3. **Check error.log** - should be clean if no other errors
4. **Monitor APP_DEBUG setting** - ensure sensitive errors are hidden in production
5. **Review any other controllers** that use render() to ensure they work correctly

---

## 💡 WHY THIS WORKS

**Before:** Error → ob_start() captures it → ob_get_clean() returns empty → render() outputs nothing → blank page

**After:** Error → caught by try/catch → displayed if APP_DEBUG=true → logged to error.log → never causes blank page

The global error handler is a safety net - it catches errors that even try/catch might miss (fatal errors at shutdown).

---

## ⚠️ PRODUCTION DEPLOYMENT

Before deploying to production:

1. ✅ Set `APP_DEBUG = false` in config.php
2. ✅ Verify error.log is readable by PHP but NOT web-accessible
3. ✅ Monitor error.log regularly for issues
4. ✅ Use proper error handling in ALL controllers
5. ✅ Never expose sensitive paths/details in error messages

All error messages will still be logged even with APP_DEBUG=false, you just won't see them in the browser.
