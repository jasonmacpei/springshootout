<?php
/**
 * test_phone_formatting.php
 * 
 * A simple script to test the phone number formatting functionality.
 * This script simulates the JavaScript and PHP formatting/cleaning
 * without actually interacting with the database.
 */

// Display errors for development
ini_set('display_errors', 1);
error_reporting(E_ALL);

/**
 * Simulates the JavaScript phone formatting 
 * (what happens on the frontend as the user types)
 */
function formatPhoneNumber($input) {
    // Strip non-digits
    $digits = preg_replace('/\D/', '', $input);
    
    // Limit to 10 digits
    $digits = substr($digits, 0, 10);
    
    // Format the phone number according to our pattern
    $formattedNumber = '';
    if (strlen($digits) > 0) {
        // Format: (xxx)
        $formattedNumber = '(' . substr($digits, 0, 3);
        
        if (strlen($digits) > 3) {
            // Format: (xxx) xxx
            $formattedNumber .= ') ' . substr($digits, 3, 3);
            
            if (strlen($digits) > 6) {
                // Format: (xxx) xxx-xxxx
                $formattedNumber .= '-' . substr($digits, 6, 4);
            }
        }
    }
    
    return $formattedNumber;
}

/**
 * Simulates the PHP phone cleaning 
 * (what happens on the backend before storing in the database)
 */
function cleanPhoneNumber($input) {
    return preg_replace('/\D/', '', $input);
}

// Test cases
$testCases = [
    '1234567890',
    '123 456 7890',
    '(123) 456-7890',
    '123-456-7890',
    '123.456.7890',
    '(123)456-7890',
    '12345',  // incomplete number
    'abc123xyz456',  // mixed characters
    '1 2 3 4 5 6 7 8 9 0',  // widely spaced 
    '(123) abc-7890',  // partial formatting with characters
    '+1 (123) 456-7890',  // with country code
];

echo "=== PHONE FORMATTING TEST ===\n\n";
echo "Testing both frontend formatting (JavaScript) and backend cleaning (PHP)\n\n";

echo str_pad("Input", 25) . " | " . 
     str_pad("Frontend Format", 18) . " | " . 
     str_pad("Backend Cleaned", 15) . " | " . 
     "Valid?\n";
echo str_repeat("-", 70) . "\n";

foreach ($testCases as $input) {
    $formatted = formatPhoneNumber($input);
    $cleaned = cleanPhoneNumber($input);
    $isValid = (strlen($cleaned) === 10) ? "✅ VALID" : "❌ INVALID";
    
    echo str_pad($input, 25) . " | " . 
         str_pad($formatted, 18) . " | " . 
         str_pad($cleaned, 15) . " | " . 
         $isValid . "\n";
}

// Simulate user typing character by character
echo "\n=== SIMULATING USER TYPING ===\n";
$userInput = '1234567890';
$currentInput = '';

for ($i = 0; $i < strlen($userInput); $i++) {
    $currentInput .= $userInput[$i];
    $formatted = formatPhoneNumber($currentInput);
    echo "After typing '" . $userInput[$i] . "': " . str_pad($formatted, 15) . "\n";
} 