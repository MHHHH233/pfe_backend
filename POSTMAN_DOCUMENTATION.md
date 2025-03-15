# Postman Documentation for Admin/V1 Controllers

## AcademieCoachController

### Get All Coaches
- **URL:** `/api/admin/v1/academie-coaches`
- **Method:** `GET`
- **Query Parameters:**
  - `include` (optional, string): Comma-separated list of relationships to include (e.g., `academie`).
  - `paginationSize` (optional, integer): Number of items per page.
  - `sort_by` (optional, string): Field to sort by (`nom`, `id_academie`).
  - `sort_order` (optional, string): Sort order (`asc`, `desc`).
  - `search` (optional, string): Search term.
  - `id_academie` (optional, integer): Filter by academie ID.
- **Responses:**
  - `200 OK`: Returns a paginated list of coaches.
  - `400 Bad Request`: Validation error.

### Get Coach by ID
- **URL:** `/api/admin/v1/academie-coaches/{id}`
- **Method:** `GET`
- **URL Parameters:**
  - `id` (required, integer): ID of the coach.
- **Query Parameters:**
  - `include` (optional, string): Comma-separated list of relationships to include (e.g., `academie`).
- **Responses:**
  - `200 OK`: Returns the coach details.
  - `404 Not Found`: Coach not found.
  - `400 Bad Request`: Validation error.

### Create Coach
- **URL:** `/api/admin/v1/academie-coaches`
- **Method:** `POST`
- **Body Parameters:**
  - `id_academie` (required, integer): Academie ID.
  - `nom` (required, string): Name of the coach.
  - `pfp` (optional, string): Profile picture URL.
  - `description` (optional, string): Description of the coach.
  - `instagram` (optional, string): Instagram handle.
- **Responses:**
  - `201 Created`: Coach created successfully.
  - `400 Bad Request`: Validation error.
  - `500 Internal Server Error`: Failed to create coach.

### Update Coach
- **URL:** `/api/admin/v1/academie-coaches/{id}`
- **Method:** `PUT`
- **URL Parameters:**
  - `id` (required, integer): ID of the coach.
- **Body Parameters:**
  - `id_academie` (required, integer): Academie ID.
  - `nom` (required, string): Name of the coach.
  - `pfp` (optional, string): Profile picture URL.
  - `description` (optional, string): Description of the coach.
  - `instagram` (optional, string): Instagram handle.
- **Responses:**
  - `200 OK`: Coach updated successfully.
  - `404 Not Found`: Coach not found.
  - `400 Bad Request`: Validation error.
  - `500 Internal Server Error`: Failed to update coach.

### Delete Coach
- **URL:** `/api/admin/v1/academie-coaches/{id}`
- **Method:** `DELETE`
- **URL Parameters:**
  - `id` (required, integer): ID of the coach.
- **Responses:**
  - `204 No Content`: Coach deleted successfully.
  - `404 Not Found`: Coach not found.
  - `500 Internal Server Error`: Failed to delete coach.

## AcademieActivitesController

### Get All Activities
- **URL:** `/api/admin/v1/academie-activities`
- **Method:** `GET`
- **Query Parameters:**
  - `include` (optional, string): Comma-separated list of relationships to include (e.g., `academie`, `members`).
  - `paginationSize` (optional, integer): Number of items per page.
  - `sort_by` (optional, string): Field to sort by (`title`, `date_debut`, `date_fin`).
  - `sort_order` (optional, string): Sort order (`asc`, `desc`).
  - `search` (optional, string): Search term.
  - `id_academie` (optional, integer): Filter by academie ID.
- **Responses:**
  - `200 OK`: Returns a paginated list of activities.
  - `400 Bad Request`: Validation error.

### Get Activity by ID
- **URL:** `/api/admin/v1/academie-activities/{id}`
- **Method:** `GET`
- **URL Parameters:**
  - `id` (required, integer): ID of the activity.
- **Query Parameters:**
  - `include` (optional, string): Comma-separated list of relationships to include (e.g., `academie`, `members`).
- **Responses:**
  - `200 OK`: Returns the activity details.
  - `404 Not Found`: Activity not found.
  - `400 Bad Request`: Validation error.

### Create Activity
- **URL:** `/api/admin/v1/academie-activities`
- **Method:** `POST`
- **Body Parameters:**
  - `id_academie` (required, integer): Academie ID.
  - `title` (required, string): Title of the activity.
  - `description` (optional, string): Description of the activity.
  - `date_debut` (required, date): Start date of the activity.
  - `date_fin` (required, date): End date of the activity.
- **Responses:**
  - `201 Created`: Activity created successfully.
  - `400 Bad Request`: Validation error.
  - `500 Internal Server Error`: Failed to create activity.

### Update Activity
- **URL:** `/api/admin/v1/academie-activities/{id}`
- **Method:** `PUT`
- **URL Parameters:**
  - `id` (required, integer): ID of the activity.
- **Body Parameters:**
  - `id_academie` (required, integer): Academie ID.
  - `title` (required, string): Title of the activity.
  - `description` (optional, string): Description of the activity.
  - `date_debut` (required, date): Start date of the activity.
  - `date_fin` (required, date): End date of the activity.
- **Responses:**
  - `200 OK`: Activity updated successfully.
  - `404 Not Found`: Activity not found.
  - `400 Bad Request`: Validation error.
  - `500 Internal Server Error`: Failed to update activity.

### Delete Activity
- **URL:** `/api/admin/v1/academie-activities/{id}`
- **Method:** `DELETE`
- **URL Parameters:**
  - `id` (required, integer): ID of the activity.
- **Responses:**
  - `204 No Content`: Activity deleted successfully.
  - `404 Not Found`: Activity not found.
  - `500 Internal Server Error`: Failed to delete activity.
