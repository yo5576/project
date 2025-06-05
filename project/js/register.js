// Wait for DOM to load
document.addEventListener('DOMContentLoaded', function() {
    // Cache DOM elements
    const roleSelect = document.getElementById('role');
    const voterFields = document.getElementById('voterFields');
    const candidateFields = document.getElementById('candidateFields');
    const videoElement = document.getElementById('faceVideo');
    const canvasElement = document.getElementById('faceCanvas');
    const captureBtn = document.getElementById('captureBtn');
    const faceFeedback = document.getElementById('faceFeedback');
    const faceDataInput = document.getElementById('face_data'); // This will now store the encoding
    
    // URL for the Python backend endpoint
    const PYTHON_BACKEND_URL = 'http://localhost:5000/process_face'; // Adjust if your backend is elsewhere

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
            faceFeedback.textContent = "Camera started. Position your face in the frame and click 'Capture Face'";
             // Hide canvas, show video
            if (videoElement) videoElement.style.display = 'block';
            if (canvasElement) canvasElement.style.display = 'none';

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

    // Handle role selection change
    if (roleSelect) {
        roleSelect.addEventListener('change', function() {
            const selectedRole = this.value;
            
            // Hide all role-specific fields first
            if (voterFields) voterFields.style.display = 'none';
            if (candidateFields) candidateFields.style.display = 'none';
            
            // Show fields based on selected role
            if (selectedRole === 'voter' && voterFields) {
                voterFields.style.display = 'block';
                // Start camera if voter is selected
                 startVideo();
            } else if (selectedRole === 'candidate' && candidateFields) {
                candidateFields.style.display = 'block';
                // Stop camera if candidate is selected
                stopVideo();
            } else {
                // Stop camera if no role is selected
                 stopVideo();
            }
             // Clear face data and feedback when role changes
            faceDataInput.value = '';
            if (faceFeedback) faceFeedback.textContent = '';
            if (canvasElement) canvasElement.style.display = 'none';
            if (videoElement) videoElement.style.display = 'block';
        });
    }
    
    // Handle face capture
    if (captureBtn && canvasElement && videoElement && faceDataInput) {
        captureBtn.addEventListener('click', async function() {
            if (!isStreaming || !videoElement.srcObject) {
                if (faceFeedback) faceFeedback.textContent = "Camera not started. Please allow camera access.";
                return;
            }
            
            if (faceFeedback) faceFeedback.textContent = "Capturing and processing face...";
            if (captureBtn) captureBtn.disabled = true;
            faceDataInput.value = ''; // Clear previous data

            // Draw current frame to canvas
            const context = canvasElement.getContext('2d');
            canvasElement.width = videoElement.videoWidth;
            canvasElement.height = videoElement.videoHeight;
            context.drawImage(videoElement, 0, 0, canvasElement.width, canvasElement.height);
            
            // Get image data as base64
            const imageDataUrl = canvasElement.toDataURL('image/png');

            try {
                // Send image data to Python backend
                const response = await fetch(PYTHON_BACKEND_URL, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ face_data: imageDataUrl }),
                });

                const result = await response.json();

                if (response.ok) {
                    if (result.status === 'success' && result.encoding) {
                        // Store the face encoding (convert back to string for hidden input)
                        faceDataInput.value = JSON.stringify(result.encoding);
                        if (faceFeedback) faceFeedback.textContent = "Face captured and processed successfully!";
                         if (captureBtn) captureBtn.textContent = "Recapture Face";
                         // Optionally hide video and show canvas
                        if (videoElement) videoElement.style.display = 'none';
                        if (canvasElement) canvasElement.style.display = 'block';

                    } else if (result.status === 'no_face') {
                         if (faceFeedback) faceFeedback.textContent = "No face detected. Please position your face properly in the frame.";
                         // Clear face data as no valid face was captured
                         faceDataInput.value = '';
                         // Keep video visible to allow repositioning
                         if (videoElement) videoElement.style.display = 'block';
                         if (canvasElement) canvasElement.style.display = 'none';
                    } else {
                         // Handle other potential success statuses or unexpected responses
                         if (faceFeedback) faceFeedback.textContent = result.message || "An unexpected issue occurred during face processing.";
                          faceDataInput.value = '';
                    }
                } else {
                    // Handle HTTP errors (e.g., 400, 500)
                    throw new Error(result.message || `HTTP error! status: ${response.status}`);
                }

            } catch (error) {
                console.error('Error sending image to backend:', error);
                if (faceFeedback) faceFeedback.textContent = `Error processing face: ${error.message}.`;
                 faceDataInput.value = '';
            } finally {
                 if (captureBtn) captureBtn.disabled = false;
            }
        });
    }
    
     // Initial check for role on page load (if form is pre-filled)
    if (roleSelect && roleSelect.value === 'voter') {
        startVideo();
    }

    // Form validation before submission
    const registrationForm = document.getElementById('registrationForm');
    if (registrationForm) {
        registrationForm.addEventListener('submit', function(event) {
            const selectedRole = roleSelect ? roleSelect.value : '';
            const faceEncoding = faceDataInput ? faceDataInput.value : '';

            // For voters, require face data
            if (selectedRole === 'voter' && !faceEncoding) {
                if (faceFeedback) faceFeedback.textContent = "Face capture and processing is required for voters.";
                event.preventDefault(); // Prevent form submission
            }
        });
    }

     // Optional: Stop video stream when leaving the page
    window.addEventListener('beforeunload', stopVideo);
}); 