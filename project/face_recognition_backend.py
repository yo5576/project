from flask import Flask, request, jsonify
from flask_cors import CORS
import face_recognition
import numpy as np
import base64
from io import BytesIO
from PIL import Image
import pymysql # Or your preferred database connector
import json # Import json for decoding stored encodings

# Database configuration (replace with your actual credentials and move to environment variables in production)
DB_CONFIG = {
    'host': 'localhost',
    'user': 'root',
    'password': '',  # Empty password
    'db': 'election_system',
    'cursorclass': pymysql.cursors.DictCursor, # Optional: returns results as dictionaries
    'connect_timeout': 10  # Add timeout to prevent hanging
}

app = Flask(__name__)
CORS(app) # Enable CORS if your frontend is on a different origin

# Helper function to get database connection
def get_db_connection():
    try:
        connection = pymysql.connect(**DB_CONFIG)
        return connection
    except pymysql.Error as e:
        print(f"Database connection error: {e}")
        raise Exception(f"Database connection failed: {e}")

@app.route('/process_face', methods=['POST'])
def process_face():
    data = request.json
    if not data or 'face_data' not in data:
        return jsonify({'status': 'error', 'message': 'No face_data provided'}), 400

    face_data_url = data['face_data']

    # Extract base64 image data
    if 'base64,' in face_data_url:
        _, base64_image = face_data_url.split('base64,', 1)
    else:
        return jsonify({'status': 'error', 'message': 'Invalid image data format'}), 400

    try:
        # Decode base64 image data and load image
        image_bytes = base64.b64decode(base64_image)
        image_file = BytesIO(image_bytes)
        image = face_recognition.load_image_file(image_file)

        # Find face locations and encodings
        face_locations = face_recognition.face_locations(image)
        face_encodings = face_recognition.face_encodings(image, face_locations)

        if not face_encodings:
            return jsonify({'status': 'no_face', 'message': 'No face detected in the image.'}), 400

        # For simplicity, assume only one face is in the image for now
        face_encoding = face_encodings[0]

        # Convert the numpy array encoding to a list to send in JSON
        encoding_list = face_encoding.tolist()

        return jsonify({'status': 'success', 'encoding': encoding_list}), 200

    except Exception as e:
        print(f"Error processing image for encoding: {e}")
        return jsonify({'status': 'error', 'message': f'Error processing image: {e}'}), 500

@app.route('/recognize_face', methods=['POST'])
def recognize_face():
    data = request.json
    if not data or 'face_data' not in data:
        return jsonify({'status': 'error', 'message': 'No face_data provided'}), 400

    face_data_url = data['face_data']

    # Extract base64 image data
    if 'base64,' in face_data_url:
        _, base64_image = face_data_url.split('base64,', 1)
    else:
        return jsonify({'status': 'error', 'message': 'Invalid image data format'}), 400

    try:
        # Decode base64 image data and load image
        image_bytes = base64.b64decode(base64_image)
        image_file = BytesIO(image_bytes)
        image = face_recognition.load_image_file(image_file)

        # Find face locations and encodings in the submitted image
        face_locations = face_recognition.face_locations(image)
        face_encodings = face_recognition.face_encodings(image, face_locations)

        if not face_encodings:
            return jsonify({'status': 'no_face', 'message': 'No face detected in the image.'}), 200

        # Assume one face in the login image
        unknown_face_encoding = face_encodings[0]

        # Connect to the database and fetch all user face encodings
        connection = get_db_connection()
        try:
            with connection.cursor() as cursor:
                # Select user id and face encoding for users who have one
                sql = "SELECT id, face_encoding FROM users WHERE face_encoding IS NOT NULL"
                cursor.execute(sql)
                registered_users = cursor.fetchall()

            if not registered_users:
                return jsonify({'status': 'no_registered_faces', 'message': 'No registered faces found in the database.'}), 200

            # Prepare known face encodings and corresponding user IDs
            known_face_encodings = []
            registered_user_ids = []

            for user in registered_users:
                try:
                    # Decode the stored JSON string encoding back to a list/array
                    stored_encoding = json.loads(user['face_encoding'])
                    # Convert list back to numpy array for face_recognition comparison
                    known_face_encodings.append(np.array(stored_encoding))
                    registered_user_ids.append(user['id'])
                except (json.JSONDecodeError, TypeError, ValueError) as e:
                    print(f"Error decoding face encoding for user {user['id']}: {e}")
                    # Skip this user if encoding is invalid
                    continue

            # Compare the unknown face to the known faces
            # The second parameter is the tolerance. Lower is stricter. 0.6 is a common default.
            matches = face_recognition.compare_faces(known_face_encodings, unknown_face_encoding, tolerance=0.6)
            face_distances = face_recognition.face_distance(known_face_encodings, unknown_face_encoding)

            best_match_index = np.argmin(face_distances)
            
            # Check if the best match is within the tolerance
            if matches[best_match_index]:
                user_id = registered_user_ids[best_match_index]
                return jsonify({'status': 'success', 'message': 'Face recognized.', 'user_id': user_id}), 200
            else:
                return jsonify({'status': 'no_match', 'message': 'Face not recognized.'}), 200

        finally:
            connection.close()

    except Exception as e:
        print(f"Error during face recognition process: {e}")
        return jsonify({'status': 'error', 'message': f'Error during recognition: {e}'}), 500

@app.route('/')
def index():
    return "Face Recognition Backend Running"

if __name__ == '__main__':
    # You might want to run this with gunicorn or other production server in production
    # Ensure host is '0.0.0.0' to be accessible externally if needed
    app.run(debug=True, port=5000, host='0.0.0.0') 