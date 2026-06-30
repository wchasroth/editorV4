import sys
import os
import cv2
import shutil

def main():
    if len(sys.argv) < 3:
        print("Usage: python crop_face.py <input_path> <output_path>")
        sys.exit(1)

    input_path = sys.argv[1]
    output_path = sys.argv[2]

    if not os.path.exists(input_path):
        print(f"Error: Input file {input_path} does not exist")
        sys.exit(1)

    try:
        # Load image
        img = cv2.imread(input_path)
        if img is None:
            # Fallback if image cannot be read by OpenCV (e.g. unsupported format)
            shutil.copy(input_path, output_path)
            print("Warning: OpenCV could not read the image. Copied original as fallback.")
            sys.exit(0)

        # Convert to grayscale for detection
        gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)

        # Load OpenCV's built-in face cascade
        cascade_path = cv2.data.haarcascades + 'haarcascade_frontalface_default.xml'
        face_cascade = cv2.CascadeClassifier(cascade_path)

        # Detect faces
        faces = face_cascade.detectMultiScale(gray, scaleFactor=1.1, minNeighbors=5, minSize=(30, 30))

        if len(faces) > 0:
            # Take the largest face if multiple are found
            largest_face = max(faces, key=lambda f: f[2] * f[3])
            x, y, w, h = largest_face
            
            # Calculate expanded margins for a professional headshot (hair and shoulders)
            height, width, _ = img.shape
            
            # Expand margins:
            # Top: 80% of face height (to get more hair/headroom)
            # Bottom: 110% of face height (to get more of the shoulders/chest)
            # Left & Right: 60% of face width (to get wider shoulders)
            top = int(max(0, y - h * 0.80))
            bottom = int(min(height, y + h * 2.10))
            left = int(max(0, x - w * 0.60))
            right = int(min(width, x + w * 1.60))
            
            # Crop and save
            crop = img[top:bottom, left:right]
            cv2.imwrite(output_path, crop)
            print("Success: Headshot extracted and saved.")
            sys.exit(0)
        else:
            # Fallback: Copy the original file
            shutil.copy(input_path, output_path)
            print("Warning: No face detected. Copied original image as fallback.")
            sys.exit(0)

    except Exception as e:
        # In case of any scripting errors, copy the original file as fallback to be safe
        try:
            shutil.copy(input_path, output_path)
            print(f"Warning: Script error occurred ({e}). Copied original image as fallback.")
            sys.exit(0)
        except Exception as copy_err:
            print(f"Error: Script failed and copy fallback failed: {copy_err}")
            sys.exit(1)

if __name__ == "__main__":
    main()
