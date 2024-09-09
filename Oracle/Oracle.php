<!DOCTYPE html>
<html>
<head>
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
    <h2>by Anthro Teacher and ChatGPT</h2>
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
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['query'])) {
    // Step 1: Retrieve the original query from the input
    $original_query = $_POST['query'];

    // Step 2: Generate a random seed based on the current time
    $seed = time(); // Use the current Unix timestamp as the seed

    // Step 3: Initialize new_query with original_query
    $new_query = $original_query;

    // Step 4: Repeatedly hash the query 888 times with SHA256
    for ($i = 0; $i < 888; $i++) {
        $new_query = hash('sha256', $original_query . ':' . $new_query . ':' . $seed);
    }

    // Step 5: Convert the hash value to an integer (selectedcard) from 1 to 176
    $selectedcard = intval(hexdec(substr($new_query, 0, 8))) % 176 + 1; // Convert hex to int and get a number between 1-176

    // Step 6: Determine card image
    $category_number = ceil($selectedcard / 4); // Determine category number (1 to 44)
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
            break;
        }
    }

    // Step 8: Display the selected card and details
    echo "<table width='800' style='margin-top: 20px;'>
            <tr>
                <td><br><img src='./$cardimage' alt='Card Image'><br></td>
            </tr>
            <tr>
                <td><h2>$category_text - $topic_text</h2></td>
            </tr>
            <tr>
                <td>$details</td>
            </tr>
          </table>";
}
?>
</body>
</html>
