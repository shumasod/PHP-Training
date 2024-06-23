<?php
/**
 * A simple implementation of the "old" function.
 *
 * @param string $inputName The name of the input field to retrieve the value for.
 * @return string The value of the input field if it exists in the POST data, otherwise an empty string.
 */
function old($inputName)
{
    // Check if the request method is POST
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Check if the input data is set in the POST data
        if (isset($_POST[$inputName])) {
            // Return the value of the input field, properly sanitized
            return htmlspecialchars($_POST[$inputName], ENT_QUOTES);
        }
    }

    // Return an empty string if the input data is not set or the request method is not POST
    return '';
}

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the input data
    $username = old('username');
    $email = old('email');

    // Perform input validation or other processing here

    // Assuming there is a form to be redisplayed, use the old function to populate the previous input values
    echo '<form method="post">';
    echo 'Username: <input type="text" name="username" value="' . $username . '"><br>';
    echo 'Email: <input type="email" name="email" value="' . $email . '"><br>';
    // Display other form elements here
    echo '<input type="submit" value="Submit">';
    echo '</form>';
}
?>
