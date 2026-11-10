# SmartDesk – AI-Powered Support Ticket Management System

SmartDesk is a customer support ticket management system built with Laravel. It allows customers to submit support tickets without creating an account and provides support agents with a dashboard to manage, classify and respond to customer enquiries.

The system integrates AI-powered ticket classification and reply generation to help support agents handle tickets more efficiently. It also includes an FAQ knowledge base to guide AI-generated responses using predefined company information.

## Features

### Customer Support
- Public ticket submission without requiring customer authentication.
- Form validation for customer information and ticket messages.
- Automatic ticket reference generation.

### Agent Dashboard
- Secure agent login.
- View and manage submitted tickets.
- Filter tickets by category, priority and status.
- Search tickets by subject or message.
- Paginated ticket listing.
- Update ticket classification manually.
- Track whether classifications were assigned by AI or an agent.

### AI-Powered Features
- Automatic ticket classification into categories:
  - Billing
  - Technical
  - Account
  - Feature Request
  - Other
- Automatic priority assignment: Low, Medium, High and Urgent.
- Sentiment detection and ticket summarisation.
- Customer language detection.
- AI-generated draft replies in the customer's language.
- FAQ-based knowledge retrieval for relevant customer enquiries.
- Fallback responses when no relevant FAQ information is available.
- Retry functionality for failed AI classification.

### AI Reliability and Cost Management
- Background processing using Laravel queues.
- Configurable AI provider and model.
- Fake AI driver for testing without external API requests.
- Rate limiting for AI classification and draft generation.
- Draft reply caching to reduce repeated API requests.
- AI request logging, including request type, execution duration, token usage and success status.

## Technology Stack

| Component | Technology |
|---|---|
| Backend | PHP, Laravel |
| Database | MySQL |
| Frontend | Blade, JavaScript, CSS |
| AI Provider | Google Gemini |
| Background Jobs | Laravel Queue |
| Cache | Laravel Cache |
| Testing | PHPUnit / Laravel Testing |

## Requirements

Before installing SmartDesk, ensure the following are available:

- PHP 8.3+
- Composer
- Node.js and npm
- MySQL 8.0+
- Laravel 13.17+

## Installation

### 1. Clone the Repository

```bash
git clone <repository-url>
cd smartdesk
```

### 2. Install Dependencies

Install PHP dependencies:

```bash
composer install
```

Install frontend dependencies:

```bash
npm install
```

### 3. Configure Environment Variables

Copy the example environment file:

```bash
cp .env.example .env
```

Generate the application key:

```bash
php artisan key:generate
```

Update the database configuration in `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=smartdesk
DB_USERNAME=root
DB_PASSWORD=
```

Create a MySQL database named `smartdesk` before proceeding.

### 4. Configure AI Settings

SmartDesk supports two AI drivers.

**Fake Driver (Default)**

```env
AI_DRIVER=fake
```

The fake driver returns predefined AI responses and does not require an external API key. It is suitable for local development and testing.

**Google Gemini Driver**

```env
AI_DRIVER=gemini
AI_PROVIDER=gemini
AI_MODEL=gemini-3.5-flash-lite
AI_API_KEY=your_gemini_api_key
```

Replace `your_gemini_api_key` with your own API key.

Keep API keys private and never commit your actual `.env` file to version control.

### 5. Run Database Migrations and Seeders

```bash
php artisan migrate --seed
```

This creates the required database tables and seeds the default agent account and sample support tickets.

The sample ticket data is provided in:

```text
database/seeders/sample-tickets.csv
```

### 6. Build Frontend Assets

```bash
npm run build
```

## Running the Application

Start the Laravel development server:

```bash
php artisan serve
```

Start the queue worker in a separate terminal:

```bash
php artisan queue:work
```

The queue worker is required to process background AI classification jobs.

Open the application at:

```text
http://localhost:8000
```

## Default Agent Account

The following account is created by the database seeder for local development:

| Field | Value |
|---|---|
| Email | agent@smartdesk.com |
| Password | password123 |

**Note:** These are development credentials. Change them before deploying the application to a production environment.

## AI Design Decisions

### Service Abstraction

AI operations are separated behind a `TicketAiService` interface. This keeps controllers and background jobs independent of the specific AI provider and makes it easier to switch between AI implementations.

### Structured Classification

AI classification responses are expected to follow a structured JSON format. The application validates the returned fields and permitted values before saving them.

If the response is invalid, the system attempts one stricter retry. If validation still fails, the classification is marked as failed.

### Background Processing

Ticket classification is handled through Laravel queued jobs rather than blocking the customer ticket submission process. This allows customers to submit tickets without waiting for the AI response.

### Prompt Injection Protection

Customer-provided content is treated as untrusted data and separated from system instructions. The AI is instructed not to follow instructions embedded in customer messages.

The application also validates AI-generated classification values against predefined enums to ensure that only permitted values are saved.

### FAQ Knowledge Base

The application uses a local FAQ file to identify relevant company information based on keywords in the customer's ticket.

Relevant FAQ entries are included in the AI reply-generation prompt. If no relevant entry is found, the AI is instructed to avoid inventing company policies or troubleshooting procedures.

### Draft Reply Caching

Generated draft replies are cached per ticket for 10 minutes to reduce unnecessary repeated AI requests.

### AI Provider and Model

SmartDesk uses Google Gemini with the `gemini-3.5-flash-lite` model.

I chose Gemini because it offers a free tier for development and testing, and is straightforward to integrate using Laravel's HTTP client. The selected model is suitable for lightweight tasks such as ticket classification and draft reply generation.

### Handling Invalid AI Responses

The application validates AI responses to ensure they follow the expected JSON structure and contain only permitted values.

If a response is invalid, the application retries once using stricter instructions. If the second attempt also fails, the ticket's AI status is marked as failed and the raw response is logged for debugging. The ticket remains accessible to the support agent.

### Development Information

- **Estimated development time:** 8 hours.
- **AI coding tool used:** ChatGPT.

ChatGPT was used to help understand the requirements, explore implementation approaches, troubleshoot errors and review parts of the code. The implementation was also tested and reviewed to understand how the application works.

### Future Improvements

- **Semantic FAQ Search:** Improve the current keyword-based FAQ matching to retrieve more relevant information.
- **AI Monitoring:** Introduce better monitoring and alerts to identify AI service failures and API errors.
- **Dashboard Analytics:** Add more detailed reports to help agents understand ticket trends and classification results.

### Demo Video

**Video link:** 

## Running Tests

Run the automated test suite using:

```bash
php artisan test
```

## Project Structure

Important application directories:

```text
app/
├── Contracts/
├── Enums/
├── Http/
├── Jobs/
├── Models/
└── Services/
    └── Ai/

config/
    └── ai.php

database/
├── migrations/
└── seeders/

resources/
└── prompts/
    ├── classification-system.txt
    ├── classification-user.txt
    ├── draft-system.txt
    ├── draft-user.txt
    └── faq.txt
```

## License

This project was developed as part of a technical assessment.