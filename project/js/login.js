// Wait for DOM to load
document.addEventListener('DOMContentLoaded', function() {
    // Cache DOM elements
    const videoElement = document.getElementById('faceVideo');
    const canvasElement = document.getElementById('faceCanvas');
    const faceFeedback = document.getElementById('faceFeedback');
    const startRecognitionBtn = document.getElementById('startRecognitionBtn');
    const recognitionLoader = document.getElementById('recognitionLoader');
    const loginMessage = document.getElementById('loginMessage');
    
    // URL for the Python backend recognition endpoint
    const PYTHON_RECOGNITION_URL = 'http://localhost:5000/recognize_face'; // Adjust if your backend is elsewhere
    // URL for the PHP login endpoint
    const PHP_LOGIN_URL = 'login.php';

    let isStreaming = false;
    
    // Start video stream
    async function startVideo() {
        if (isStreaming) return; // Prevent starting if already streaming
        try {
             // Request camera access with specific constraints
            const constraints = {
                video: {
                    width: { ideal: 400 },
                    height: { ideal: 300 },
                    facingMode: 'user'
                }
            };
            const stream = await navigator.mediaDevices.getUserMedia(constraints);
            videoElement.srcObject = stream;
            isStreaming = true;
            faceFeedback.textContent = "Camera started. Position your face in the frame and click 'Start Face Recognition'";
             // Hide canvas, show video
            if (videoElement) videoElement.style.display = 'block';
            if (canvasElement) canvasElement.style.display = 'none';
            if (loginMessage) loginMessage.style.display = 'none'; // Hide previous messages

        } catch (error) {
            console.error('Error accessing webcam:', error);
            // Provide more specific error messages based on the error type
            if (error.name === 'NotAllowedError') {
                faceFeedback.textContent = "Camera access denied. Please allow camera access in your browser settings and refresh the page.";
            } else if (error.name === 'NotFoundError') {
                faceFeedback.textContent = "No camera found on this device. Please connect a camera.";
            } else if (error.name === 'NotReadableError') {
                faceFeedback.textContent = "Camera is already in use or not accessible. Please close other applications using the camera.";
            } else if (error.name === 'OverconstrainedError') {
                faceFeedback.textContent = "Camera constraints not supported by your device. Please try a different browser or device.";
            } else if (error.name === 'SecurityError') {
                faceFeedback.textContent = "Camera access blocked by security settings (e.g., insecure connection). Use HTTPS.";
            } else {
                faceFeedback.textContent = `Error accessing camera: ${error.message}. Please try again or check console for details.`;
            }
            isStreaming = false;
        }
    }
    
    // Stop video stream
    function stopVideo() {
        const stream = videoElement.srcObject;
        if (stream) {
            const tracks = stream.getTracks();
            tracks.forEach(track => track.stop());
            videoElement.srcObject = null;
            isStreaming = false;
             // Hide video and canvas
            if (videoElement) videoElement.style.display = 'none';
            if (canvasElement) canvasElement.style.display = 'none';
        }
    }
    
    // Helper to show messages
    function showMessage(message, type) {
        if (loginMessage) {
            loginMessage.textContent = message;
            loginMessage.className = `alert ${type === 'error' ? 'error' : 'success'}`;
            loginMessage.style.display = 'block';
        }
        if (faceFeedback) faceFeedback.textContent = ''; // Clear face feedback
    }

    // Start face recognition process
    async function startRecognitionProcess() {
        if (!isStreaming || !videoElement.srcObject) {
            showMessage("Camera not started. Please allow camera access.", "error");
            return;
        }
        
        // Show loader and disable button
        if (recognitionLoader) recognitionLoader.style.display = 'flex';
        if (startRecognitionBtn) startRecognitionBtn.disabled = true;
        if (faceFeedback) faceFeedback.textContent = "Looking for your face...";
        if (loginMessage) loginMessage.style.display = 'none'; // Hide previous messages

        try {
            // Draw current frame to canvas
            const context = canvasElement.getContext('2d');
            canvasElement.width = videoElement.videoWidth;
            canvasElement.height = videoElement.videoHeight;
            context.drawImage(videoElement, 0, 0, canvasElement.width, canvasElement.height);
            
            // Get image data as base64
            const imageDataUrl = canvasElement.toDataURL('image/png');

            // Send image data to Python backend for recognition
            const pythonResponse = await fetch(PYTHON_RECOGNITION_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ face_data: imageDataUrl }),
            });

            const pythonResult = await pythonResponse.json();

            if (pythonResponse.ok) {
                if (pythonResult.status === 'success' && pythonResult.user_id) {
                    // Face recognized by Python, now inform login.php to log in
                    showMessage("Face recognized! Attempting login...", "success");
                    stopVideo(); // Stop camera after successful recognition

                    // Send user_id to login.php to complete authentication
                    const phpLoginResponse = await fetch(PHP_LOGIN_URL, {
                         method: 'POST',
                         headers: {
                             'Content-Type': 'application/json',
                         },
                         body: JSON.stringify({ user_id: pythonResult.user_id }), // Send user_id in JSON body
                    });

                    const phpLoginResult = await phpLoginResponse.json();

                    if (phpLoginResponse.ok && phpLoginResult.status === 'success' && phpLoginResult.redirect) {
                        // PHP login successful, redirect the user
                        showMessage("Login successful! Redirecting...", "success");
                        window.location.href = phpLoginResult.redirect;
                    } else {
                        // PHP login failed (e.g., user not found in DB after recognition)
                         showMessage(phpLoginResult.message || 'PHP login failed.', "error");
                         // Re-show video if PHP login failed
                         if (videoElement) videoElement.style.display = 'block';
                         if (canvasElement) canvasElement.style.display = 'none';
                    }

                } else if (pythonResult.status === 'no_face') {
                    showMessage("No face detected. Please position your face properly in the frame and try again.", "error");
                     // Keep video visible
                    if (videoElement) videoElement.style.display = 'block';
                    if (canvasElement) canvasElement.style.display = 'none';

                } else if (pythonResult.status === 'no_match') {
                     showMessage("Face not recognized. Please try again or use password login.", "error");
                      // Keep video visible
                    if (videoElement) videoElement.style.display = 'block';
                    if (canvasElement) canvasElement.style.display = 'none';
                }
                 else {
                    // Handle other potential success statuses from Python
                    showMessage(pythonResult.message || "An unexpected issue occurred during face recognition.", "error");
                }
            } else {
                // Handle HTTP errors from Python backend
                throw new Error(pythonResult.message || `Python backend HTTP error! status: ${pythonResponse.status}`);
            }

        } catch (error) {
            console.error('Error during face recognition process:', error);
            showMessage(`Error during face recognition: ${error.message}. Please try again.`, "error");
             // Ensure video is visible on error
             if (videoElement) videoElement.style.display = 'block';
             if (canvasElement) canvasElement.style.display = 'none';
        } finally {
            // Hide loader and re-enable button (if not redirecting)
             if (recognitionLoader) recognitionLoader.style.display = 'none';
             // Re-enable button only if no successful login attempt was initiated
             if (startRecognitionBtn && !loginMessage.textContent.includes('Login successful')) startRecognitionBtn.disabled = false; 
        }
    }
    
    // Event listeners
    if (startRecognitionBtn) {
        startRecognitionBtn.addEventListener('click', startRecognitionProcess);
    }
    
    // Initialize camera when the face login section is visible
    // Assuming the face login section is initially displayed or shown based on user action
    // You might need to adjust this based on how your login form switches between password and face login
     const faceLoginContainer = document.querySelector('.form-container.face-login');
    if (faceLoginContainer && faceLoginContainer.style.display !== 'none') {
         startVideo();
     }

    // Optional: Stop video stream when leaving the page
    window.addEventListener('beforeunload', stopVideo);
}); 