# Contact API

Public contact form for submitting inquiries. No authentication required.

**Base path**: `/api/v1`

---

## Endpoints Summary

| Method | Endpoint | Access | Description |
|--------|----------|--------|-------------|
| POST | `/contacts` | Public | Submit contact form |

---

## Submit Contact Form

| | |
|---|---|
| **Endpoint** | `POST /contacts` |
| **Access** | Public |
| **Content-Type** | `application/json` |

### Request Body
```json
{
  "first_name": "Jane",
  "last_name": "Smith",
  "email": "jane@example.com",
  "phone": "+919876543210",
  "subject": "General Inquiry",
  "message": "I would like to know more about your tutoring services."
}
```

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| first_name | string | Yes | Max 100 chars |
| last_name | string | Yes | Max 100 chars |
| email | string | Yes | Valid email |
| phone | string | No | Max 30 chars |
| subject | string | Yes | Max 255 chars |
| message | string | Yes | Max 5000 chars |

### Success (201)
```json
{
  "success": true,
  "message": "Contact form submitted successfully.",
  "data": {
    "id": 1,
    "first_name": "Jane",
    "last_name": "Smith",
    "email": "jane@example.com",
    "phone": "+919876543210",
    "subject": "General Inquiry",
    "message": "I would like to know more about your tutoring services.",
    "status": "new",
    "created_at": "2025-03-11T10:00:00.000000Z"
  }
}
```

### Validation Error (422)
```json
{
  "success": false,
  "message": "Validation failed",
  "code": 422,
  "errors": {
    "email": ["The email field must be a valid email address."],
    "first_name": ["The first name field is required."]
  }
}
```

---

## Example

```bash
curl -X POST http://localhost:8000/api/v1/contacts \
  -H "Content-Type: application/json" \
  -d '{
    "first_name": "Jane",
    "last_name": "Smith",
    "email": "jane@example.com",
    "subject": "General Inquiry",
    "message": "Interested in tutoring services."
  }'
```
