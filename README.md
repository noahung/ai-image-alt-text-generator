# Bulk Image Alt Text Generator

![Plugin Banner](https://via.placeholder.com/1200x300.png?text=Bulk+Image+Alt+Text+Generator)

**Generate AI-powered alt texts for multiple images in bulk within WordPress.**

The Bulk Image Alt Text Generator is a WordPress plugin that leverages the power of AI (via OpenAI’s API) to automatically generate descriptive alt texts for images in your media library. This tool is designed to improve accessibility and SEO by ensuring all your images have meaningful alt texts, saving you time and effort.

## Features

- **Bulk Alt Text Generation**: Select multiple images from your WordPress media library and generate alt texts for all of them in one go.
- **AI-Powered Descriptions**: Uses OpenAI’s GPT-4o model to create accurate and SEO-friendly alt texts.
- **User-Friendly Interface**: A clean, professional UI with thumbnail previews, a results table, and intuitive controls.
- **Customizable Prompts**: Adjust the AI prompt in the settings to tailor alt texts to your needs (e.g., SEO-focused, concise).
- **Responsive Design**: Works seamlessly on desktops, tablets, and mobile devices.
- **Loading Feedback**: A bold, flashing loading message to clearly indicate when the AI is processing images.

## Screenshots

### Main Interface
![Main Interface](https://via.placeholder.com/800x400.png?text=Main+Interface)

### Image Selection and Preview
![Image Selection](https://via.placeholder.com/800x400.png?text=Image+Selection)

### Results Table
![Results Table](https://via.placeholder.com/800x400.png?text=Results+Table)

## Installation

1. **Download the Plugin**:
   - Clone this repository: `git clone https://github.com/yourusername/bulk-image-alt-text-generator.git`
   - Alternatively, download the ZIP file from the GitHub repository and extract it.

2. **Upload to WordPress**:
   - Navigate to your WordPress admin dashboard.
   - Go to **Plugins > Add New > Upload Plugin**.
   - Upload the `bulk-image-alt-text-generator` folder (or the ZIP file).
   - Activate the plugin.

3. **Configure Settings**:
   - Go to **Alt Text Generator AI > Settings** in your WordPress admin menu.
   - Enter your OpenAI API key (available from [OpenAI](https://platform.openai.com/account/api-keys)).
   - Optionally, customize the AI prompt for generating alt texts.
   - Save your settings.

## Usage

1. **Navigate to the Plugin**:
   - In your WordPress admin dashboard, go to **Alt Text Generator AI > Generate Alt Texts**.

2. **Select Images**:
   - Click the **Select Images** button to open the WordPress media library.
   - Choose the images you want to generate alt texts for (you can select multiple images).
   - Confirm your selection.

3. **Preview Alt Texts**:
   - Once images are selected, click **Preview Alt Texts**.
   - The plugin will display a loading message (bold and flashing) while the AI processes the images.
   - A table will appear with each image, its current alt text, and the AI-generated alt text.

4. **Edit and Save**:
   - Review the generated alt texts and edit them if needed directly in the table.
   - Click **Save Alt Texts** to apply the new alt texts to your images in the media library.

## Requirements

- **WordPress**: Version 5.0 or higher.
- **PHP**: Version 7.4 or higher.
- **OpenAI API Key**: Required for AI-powered alt text generation. Sign up at [OpenAI](https://platform.openai.com/signup) to get your key.
- **Media Library Access**: The plugin requires access to your WordPress media library to fetch and update image metadata.

## Folder Structure
