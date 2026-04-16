<?php
/**
 * API Response Service
 * Provides consistent JSON responses for future API development
 */
class ApiResponse {
    
    // HTTP Status Codes
    const HTTP_OK = 200;
    const HTTP_CREATED = 201;
    const HTTP_BAD_REQUEST = 400;
    const HTTP_UNAUTHORIZED = 401;
    const HTTP_FORBIDDEN = 403;
    const HTTP_NOT_FOUND = 404;
    const HTTP_CONFLICT = 409;
    const HTTP_UNPROCESSABLE = 422;
    const HTTP_TOO_MANY_REQUESTS = 429;
    const HTTP_INTERNAL_ERROR = 500;
    
    // Response codes
    const CODE_SUCCESS = 'SUCCESS';
    const CODE_ERROR = 'ERROR';
    const CODE_VALIDATION_ERROR = 'VALIDATION_ERROR';
    const CODE_AUTH_ERROR = 'AUTH_ERROR';
    const CODE_NOT_FOUND = 'NOT_FOUND';
    const CODE_RATE_LIMITED = 'RATE_LIMITED';
    
    /**
     * Send success response
     */
    public static function success($data = null, $message = null, $meta = null) {
        $response = [
            'success' => true,
            'code' => self::CODE_SUCCESS,
            'message' => $message ?? 'Operation successful',
            'data' => $data
        ];
        
        if ($meta) {
            $response['meta'] = $meta;
        }
        
        return self::json($response, self::HTTP_OK);
    }
    
    /**
     * Send created response
     */
    public static function created($data = null, $message = 'Resource created successfully') {
        $response = [
            'success' => true,
            'code' => self::CODE_SUCCESS,
            'message' => $message,
            'data' => $data
        ];
        
        return self::json($response, self::HTTP_CREATED);
    }
    
    /**
     * Send error response
     */
    public static function error($message = 'An error occurred', $code = self::CODE_ERROR, $httpCode = self::HTTP_INTERNAL_ERROR) {
        $response = [
            'success' => false,
            'code' => $code,
            'message' => $message
        ];
        
        return self::json($response, $httpCode);
    }
    
    /**
     * Send validation error response
     */
    public static function validationError($errors, $message = 'Validation failed') {
        $response = [
            'success' => false,
            'code' => self::CODE_VALIDATION_ERROR,
            'message' => $message,
            'errors' => $errors
        ];
        
        return self::json($response, self::HTTP_UNPROCESSABLE);
    }
    
    /**
     * Send unauthorized response
     */
    public static function unauthorized($message = 'Authentication required') {
        $response = [
            'success' => false,
            'code' => self::CODE_AUTH_ERROR,
            'message' => $message
        ];
        
        return self::json($response, self::HTTP_UNAUTHORIZED);
    }
    
    /**
     * Send forbidden response
     */
    public static function forbidden($message = 'Access denied') {
        $response = [
            'success' => false,
            'code' => self::CODE_AUTH_ERROR,
            'message' => $message
        ];
        
        return self::json($response, self::HTTP_FORBIDDEN);
    }
    
    /**
     * Send not found response
     */
    public static function notFound($message = 'Resource not found') {
        $response = [
            'success' => false,
            'code' => self::CODE_NOT_FOUND,
            'message' => $message
        ];
        
        return self::json($response, self::HTTP_NOT_FOUND);
    }
    
    /**
     * Send conflict response
     */
    public static function conflict($message = 'Resource already exists') {
        $response = [
            'success' => false,
            'code' => self::CODE_ERROR,
            'message' => $message
        ];
        
        return self::json($response, self::HTTP_CONFLICT);
    }
    
    /**
     * Send rate limited response
     */
    public static function rateLimited($message = 'Too many requests', $retryAfter = null) {
        $response = [
            'success' => false,
            'code' => self::CODE_RATE_LIMITED,
            'message' => $message
        ];
        
        if ($retryAfter) {
            $response['retry_after'] = $retryAfter;
        }
        
        return self::json($response, self::HTTP_TOO_MANY_REQUESTS);
    }
    
    /**
     * Send paginated response
     */
    public static function paginated($data, $page, $perPage, $total, $message = null) {
        $totalPages = ceil($total / $perPage);
        
        $response = [
            'success' => true,
            'code' => self::CODE_SUCCESS,
            'message' => $message ?? 'Operation successful',
            'data' => $data,
            'meta' => [
                'pagination' => [
                    'current_page' => (int) $page,
                    'per_page' => (int) $perPage,
                    'total' => (int) $total,
                    'total_pages' => (int) $totalPages,
                    'has_next_page' => $page < $totalPages,
                    'has_previous_page' => $page > 1
                ]
            ]
        ];
        
        return self::json($response, self::HTTP_OK);
    }
    
    /**
     * Send JSON response with proper headers
     */
    private static function json($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        
        // Remove debug info in production
        if (defined('ENVIRONMENT') && ENVIRONMENT === 'production') {
            unset($data['debug']);
        }
        
        echo json_encode($data, JSON_PRETTY_PRINT);
        exit;
    }
    
    /**
     * Convert controller result to API response
     */
    public static function fromController($result) {
        if (isset($result['success']) && $result['success'] === true) {
            return self::success(
                $result['data'] ?? null,
                $result['message'] ?? null,
                $result['meta'] ?? null
            );
        }
        
        if (isset($result['success']) && $result['success'] === false) {
            return self::error(
                $result['error'] ?? 'Operation failed',
                self::CODE_ERROR,
                $result['http_code'] ?? self::HTTP_INTERNAL_ERROR
            );
        }
        
        return self::error('Invalid response format');
    }
}
