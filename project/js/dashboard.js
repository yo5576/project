// Face Recognition Modal
const faceModal = document.getElementById('faceModal');
const video = document.getElementById('video');
const canvas = document.getElementById('canvas');
const captureBtn = document.getElementById('captureBtn');
const retakeBtn = document.getElementById('retakeBtn');
const submitBtn = document.getElementById('submitBtn');
const closeBtn = document.querySelector('.close');

let stream = null;
let capturedImage = null;

// Open modal and start camera
function registerFace() {
    faceModal.style.display = 'block';
    startCamera();
}

// Close modal and stop camera
function closeModal() {
    faceModal.style.display = 'none';
    stopCamera();
}

// Start camera stream
async function startCamera() {
    try {
        stream = await navigator.mediaDevices.getUserMedia({ 
            video: { 
                width: { ideal: 1280 },
                height: { ideal: 720 },
                facingMode: 'user'
            } 
        });
        video.srcObject = stream;
    } catch (err) {
        console.error('Error accessing camera:', err);
        alert('Error accessing camera. Please make sure you have granted camera permissions.');
        closeModal();
    }
}

// Stop camera stream
function stopCamera() {
    if (stream) {
        stream.getTracks().forEach(track => track.stop());
        video.srcObject = null;
    }
}

// Capture image from video
function captureImage() {
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    canvas.getContext('2d').drawImage(video, 0, 0);
    capturedImage = canvas.toDataURL('image/jpeg');
    
    // Show retake and submit buttons
    captureBtn.style.display = 'none';
    retakeBtn.style.display = 'inline-block';
    submitBtn.style.display = 'inline-block';
}

// Retake photo
function retakePhoto() {
    capturedImage = null;
    canvas.getContext('2d').clearRect(0, 0, canvas.width, canvas.height);
    
    // Show capture button, hide retake and submit
    captureBtn.style.display = 'inline-block';
    retakeBtn.style.display = 'none';
    submitBtn.style.display = 'none';
}

// Submit face data
async function submitFaceData() {
    if (!capturedImage) {
        alert('Please capture an image first');
        return;
    }

    try {
        const response = await fetch('/process_face', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                face_data: capturedImage
            })
        });

        const data = await response.json();

        if (data.status === 'success') {
            alert('Face registered successfully!');
            location.reload(); // Refresh to update UI
        } else {
            alert(data.message || 'Error registering face');
        }
    } catch (err) {
        console.error('Error submitting face data:', err);
        alert('Error submitting face data. Please try again.');
    }
}

// Event Listeners
captureBtn.addEventListener('click', captureImage);
retakeBtn.addEventListener('click', retakePhoto);
submitBtn.addEventListener('click', submitFaceData);
closeBtn.addEventListener('click', closeModal);

// Close modal when clicking outside
window.addEventListener('click', (e) => {
    if (e.target === faceModal) {
        closeModal();
    }
});

// Voting Functions
function startVoting() {
    if (!confirm('Are you sure you want to start the voting process? You will need to verify your face.')) {
        return;
    }
    
    // Start face verification process
    verifyFaceForVoting();
}

async function verifyFaceForVoting() {
    try {
        const stream = await navigator.mediaDevices.getUserMedia({ 
            video: { 
                width: { ideal: 1280 },
                height: { ideal: 720 },
                facingMode: 'user'
            } 
        });
        
        const video = document.createElement('video');
        video.srcObject = stream;
        await video.play();
        
        const canvas = document.createElement('canvas');
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        canvas.getContext('2d').drawImage(video, 0, 0);
        
        const imageData = canvas.toDataURL('image/jpeg');
        
        // Stop the stream
        stream.getTracks().forEach(track => track.stop());
        
        // Send to server for verification
        const response = await fetch('/recognize_face', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                face_data: imageData
            })
        });

        const data = await response.json();

        if (data.status === 'success') {
            // Redirect to voting page
            window.location.href = 'voting.php';
        } else {
            alert(data.message || 'Face verification failed. Please try again.');
        }
    } catch (err) {
        console.error('Error during face verification:', err);
        alert('Error during face verification. Please try again.');
    }
}

// Profile Update
function updateProfile() {
    window.location.href = 'update_profile.php';
}

// View Election Info
function viewElectionInfo() {
    window.location.href = 'election_info.php';
}

// Candidate Functions
function updateManifesto() {
    window.location.href = 'update_manifesto.php';
}

function viewStatistics() {
    window.location.href = 'statistics.php';
}

function manageCampaign() {
    window.location.href = 'campaign.php';
} 