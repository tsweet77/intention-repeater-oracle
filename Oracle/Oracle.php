<!DOCTYPE html>
<html>
<head>
<style>
    /* Modal styling */
    #feedbackModal {
        display: none; /* Hidden by default */
        position: fixed;
        left: 50%;
        top: 50%;
        transform: translate(-50%, -50%);
        width: 400px;
        background-color: white;
        border: 1px solid #ccc;
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        z-index: 1000; /* Above other elements */
        padding: 20px;
        border-radius: 8px;
        resize: both; /* Allows resizing */
        overflow: auto; /* Enable scrolling if content is too big */
    }

    #feedbackModal textarea {
        width: 100%;
        height: 100px;
        resize: vertical; /* Allow vertical resizing of textarea */
    }

    #feedbackOverlay {
        display: none; /* Hidden by default */
        position: fixed;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.5); /* Semi-transparent background */
        z-index: 999; /* Behind modal */
    }

    #feedbackModalHeader {
        cursor: move; /* Indicate draggable header */
        background-color: #f1f1f1;
        padding: 10px;
        border-bottom: 1px solid #ddd;
        text-align: center;
        font-weight: bold;
    }

    #feedbackButtons {
        margin-top: 10px;
        text-align: right;
    }
</style>
<!--This uses send_emails.txt to determine if to send an email. Value of 1 means to send an email with each image feedback. Can prevent abuse.-->
<!-- Feedback modal overlay -->
    <div id="feedbackOverlay"></div>

    <!-- Feedback modal -->
    <div id="feedbackModal">
        <div id="feedbackModalHeader">Feedback about the Image</div>
        <textarea id="feedbackTextarea" maxlength="5000" placeholder="Enter your feedback..."></textarea>
        <div id="feedbackButtons">
            <button onclick="submitFeedback()">Submit</button>
            <button onclick="closeFeedbackModal()">Cancel</button>
        </div>
    </div>

<script>
// Function to show the modal
function showFeedbackModal() {
    var modal = document.getElementById('feedbackModal');
    var overlay = document.getElementById('feedbackOverlay');
    modal.style.display = 'block';
    overlay.style.display = 'block';
    dragElement(modal); // Make the modal draggable
}

// Function to close the modal
function closeFeedbackModal() {
    document.getElementById('feedbackModal').style.display = 'none';
    document.getElementById('feedbackOverlay').style.display = 'none';
}

// Function to handle feedback submission
function submitFeedback() {
    var additionalInfo = document.getElementById('feedbackTextarea').value.trim();
    if (additionalInfo.length > 0) {
        closeFeedbackModal();
        reportImage(additionalInfo); // Send feedback to the server
    } else {
        alert('Please provide some feedback before submitting.');
    }
}

// Function to make the modal draggable
function dragElement(element) {
    var pos1 = 0, pos2 = 0, pos3 = 0, pos4 = 0;
    var header = document.getElementById("feedbackModalHeader");
    if (header) {
        // If present, the header is where you move the DIV from
        header.onmousedown = dragMouseDown;
    }

    function dragMouseDown(e) {
        e = e || window.event;
        e.preventDefault();
        // Get the mouse cursor position at startup
        pos3 = e.clientX;
        pos4 = e.clientY;
        document.onmouseup = closeDragElement;
        // Call a function whenever the cursor moves
        document.onmousemove = elementDrag;
    }

    function elementDrag(e) {
        e = e || window.event;
        e.preventDefault();
        // Calculate the new cursor position
        pos1 = pos3 - e.clientX;
        pos2 = pos4 - e.clientY;
        pos3 = e.clientX;
        pos4 = e.clientY;
        // Set the element's new position
        element.style.top = (element.offsetTop - pos2) + "px";
        element.style.left = (element.offsetLeft - pos1) + "px";
    }

    function closeDragElement() {
        // Stop moving when mouse button is released
        document.onmouseup = null;
        document.onmousemove = null;
    }
}


// Function to handle the feedback submission
function reportImage(additionalInfo) {
            var xhr = new XMLHttpRequest(); // Create a new XMLHttpRequest object
            var formData = new FormData(document.getElementById('reportForm')); // Create FormData object from the form

            // Manually append the button value to the FormData
            formData.append('report_improper_image', '1'); // Add the button's name to the form data

            // Append the additional information to the form data
            formData.append('additional_info', additionalInfo);

            // Define what happens on successful data submission
            xhr.onload = function() {
                if (xhr.status === 200) {
                    alert('Image Feedback Sent. Thank you.');
                } else {
                    alert('An error occurred. Please try again.');
                }
            };

            // Set up the request to the same PHP page
            xhr.open('POST', window.location.href, true); 
            xhr.send(formData); // Send the request with form data
            return false; // Prevent form submission
        }
    </script>
<?php
// Check if the form has been submitted to report an improper image
if (isset($_POST['report_improper_image'])) {
    if (isset($_POST['cardimage'])) {
        $cardimage = $_POST['cardimage']; // Correctly get the image name from POST data
        $prompt = $_POST['prompt']; // Correctly get the image name from POST data
        $cleaned_prompt = str_replace(['<?php echo htmlspecialchars(', '); ?>'], '', $prompt);
        $cleaned_cardimage = str_replace(['<?php echo htmlspecialchars(', '); ?>'], '', $cardimage);

        // Use the image filename from the hidden input
        $imageName = $cardimage;

        // Get the current date and timestamp
        $timestamp = date('Y-m-d H:i:s');

        // Retrieve additional information from the AJAX request
        $additionalInfo = isset($_POST['additional_info']) ? $_POST['additional_info'] : 'No additional information provided';

        // Append the image name, timestamp, and additional information to "reported_images.txt"
        $file = fopen("reported_images.txt", "a");
        if ($file) {
            fwrite($file, "$timestamp - $cleaned_cardimage\n");
            fwrite($file, "Additional Info: $additionalInfo\n\n");
            fwrite($file, "DALL-E Prompt: $cleaned_prompt\n\n");
            fclose($file);
        } else {
            error_log("Failed to open reported_images.txt for writing.");
        }

        // Check if the "send_emails.txt" file exists
        if (file_exists('send_emails.txt')) {
            // Read the content of the file
            $send_email_flag = trim(file_get_contents('send_emails.txt'));

            // Check if the value is "1"
            if ($send_email_flag === "1") {
                // Email details
                $to = "healing@intentionrepeater.com";
                $subject = "Intention Repeater Oracle: Image Feedback Received";
                $message = "Image Reported: " . $cleaned_cardimage . "\n\nAdditional Info: " . $additionalInfo . "\n\nDALL-E Prompt: " . $cleaned_prompt;
                $headers = "From: oracle-noreply@intentionrepeater.com\r\n";
                $headers .= "Reply-To: oracle-noreply@intentionrepeater.com\r\n";
                $headers .= "X-Mailer: PHP/" . phpversion();

                // Send the email
                mail($to, $subject, $message, $headers);
            }
        } else {
            error_log("send_emails.txt does not exist.");
        }
    }

    // Exit after handling the AJAX request to avoid further processing
    exit;
}
?>
    <title>Intention Repeater Oracle</title>
    <style>
        table {
            margin: 0 auto; /* Center the table horizontally */
            width: 800px;
        }
        textarea {
            width: 100%;
        }
        h1, h2 {
            text-align: center;
        }
        td {
            text-align: center;
        }
    </style>
</head>
<body>
    <h1>Intention Repeater Oracle</h1>
    <h2>by Anthro Teacher, ChatGPT and DALL-E</h2>
    <form method="post">
        <table>
            <tr>
                <td>Query:</td>
            </tr>
            <tr>
                <td>
                    <textarea name="query" rows="4" cols="50"><?php echo isset($_POST['query']) ? htmlspecialchars($_POST['query']) : ''; ?></textarea>
                </td>
            </tr>
            <tr>
                <td>
                    <input type="submit" name="submit" value="Submit">
                </td>
            </tr>
        </table>
    </form>

<?php

// Define the file path
$file_path = 'counter.txt';

// Check if the file exists
if (file_exists($file_path)) {
    // Read the current counter value from the file
    $counter = (int) file_get_contents($file_path);
} else {
    // If the file does not exist, initialize the counter to 0
    $counter = 0;
}

// Increment the counter by one
$counter++;

// Write the updated counter value back to the file
file_put_contents($file_path, $counter);


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['query'])) {
    // Step 1: Retrieve the original query from the input
    $original_query = $_POST['query'];

    // Step 2: Generate a random seed based on the current time
    $seed = time(); // Use the current Unix timestamp as the seed

    // Step 3: Initialize new_query with original_query
    $new_query = $original_query;

    $hash_of_file = hash_file('sha512', './Cards.txt');

    // Step 4: Repeatedly hash the query 888 times with sha512
    for ($i = 0; $i < 1111; $i++) {
        $new_query = hash('sha512', $original_query . ':' . $new_query  . ':' . $hash_of_file . ':' . $seed);
    }

    // Step 5: Convert the hash value to an integer (selectedcard) from 1 to 208
    $selectedcard = intval(hexdec(substr($new_query, 0, 8))) % 208 + 1; // Convert hex to int and get a number between 1-208

    // Step 6: Determine card image
    $category_number = ceil($selectedcard / 4); // Determine category number (1 to 52)
    $iteration_number = $selectedcard % 4 + 1;  // Determine iteration number (1-4)
    $cardimage = $category_number . '-' . $iteration_number . '.jpg';

    // Step 7: Read from Cards.txt
    $lines = file('./Cards.txt');
    $category_text = '';
    $topic_text = '';
    $details = '';

    foreach ($lines as $line) {
        $line_number = strtok($line, '.');
        if (intval($line_number) == $category_number) {
            // Extract category text between the 2nd ". " and the first " -"
            $category_text = trim(substr($line, strpos($line, '. ') + 2, strpos($line, ' -') - strpos($line, '. ') - 2));
            
            // Extract topic text between the first "- " and the 2nd " -"
            $topic_text_start = strpos($line, '- ') + 2;
            $topic_text_end = strpos($line, ' -', $topic_text_start);
            $topic_text = trim(substr($line, $topic_text_start, $topic_text_end - $topic_text_start));
            
            // Extract details between the 2nd "- " and " Prompt:"
            $details_start = strpos($line, ' - ', $topic_text_end) + 3;
            $details_end = strpos($line, 'Prompt:');
            $details = trim(substr($line, $details_start, $details_end - $details_start));

            // Extract everything after "Prompt: "
            $prompt_start = strpos($line, 'Prompt:') + strlen('Prompt: ');
            $prompt = trim(substr($line, $prompt_start));
            break;
        }
    }

    if (isset($_POST['cardimage'])) {
        $cardimage = $_POST['cardimage']; // Correctly get the image name from POST data
        echo "<script>alert('Image Reported. Thank you.');</script>";
    }

    if (isset($_POST['prompt'])) {
        $cardimage = $_POST['prompt']; // Correctly get the image name from POST data
    }
    
    // Check if details are already set; if not, assign from POST data or default value
    if (isset($_POST['details'])) {
        $details = $_POST['details']; // Replace with your dynamic variable
    }

    if (isset($_POST['category_text'])) {
        $category_text = $_POST['category_text']; // Replace with your dynamic variable
    }
    
    // Check if details are already set; if not, assign from POST data or default value
    if (isset($_POST['topic_text'])) {
        $topic_text = $_POST['topic_text']; // Replace with your dynamic variable
    }

    // Display the selected card and details
            // Display the selected card and details
            echo "<table width='800' style='margin-top: 20px;'>
            <tr>
                <td colspan=2><br><img src='./$cardimage' alt='Card Image'><br></td>
            </tr>
            <tr>
                <td colspan=2><h2>$category_text - $topic_text</h2></td>
            </tr>
            <tr>
                <td colspan=2>$details</td>
            </tr>
            <tr>
                <td><br><a href='https://www.intentionrepeater.com/'>INTENTION REPEATER HOME</a></td>
                <td>
                    <!-- Button to trigger the modal -->
                    <button type='button' onclick='showFeedbackModal()'>Provide Image Feedback</button>

                    <form id='reportForm' method='post' style='display:none;'>
                        <input type='hidden' name='cardimage' value='<?php echo htmlspecialchars($cardimage); ?>'>
                        <input type='hidden' name='details' value='<?php echo htmlspecialchars($details); ?>'>
                        <input type='hidden' name='category_text' value='<?php echo htmlspecialchars($category_text); ?>'>
                        <input type='hidden' name='topic_text' value='<?php echo htmlspecialchars($topic_text); ?>'>
                        <input type='hidden' name='query' value='<?php echo htmlspecialchars($query); ?>'>
                        <input type='hidden' name='prompt' value='<?php echo htmlspecialchars($prompt); ?>'>
                    </form>
                </form>
                </td>
            </tr>
        </table>";
    } else {
    echo '<center><br><a href="https://www.intentionrepeater.com/">INTENTION REPEATER HOME</a></center>';
    }
?>
</body>
</html>