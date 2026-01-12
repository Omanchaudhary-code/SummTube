import logging
import sys
import os
import json
from datetime import datetime

def setup_logging():
    """Configure application-wide logging with production optimizations"""
    logger = logging.getLogger()
    
    # Set log level based on environment
    env = os.getenv("APP_ENV", "development")
    if env == "production":
        log_level = logging.WARNING  # Only warnings and errors in production
    else:
        log_level = logging.INFO
    
    logger.setLevel(log_level)
    
    # Remove existing handlers to avoid duplicates
    logger.handlers.clear()
    
    # Console handler with structured JSON format for production
    console_handler = logging.StreamHandler(sys.stdout)
    console_handler.setLevel(log_level)
    
    if env == "production":
        # Structured JSON formatter for production (easier log aggregation)
        formatter = JsonFormatter()
    else:
        # Human-readable formatter for development
        formatter = logging.Formatter(
            '%(asctime)s - %(name)s - %(levelname)s - %(message)s',
            datefmt='%Y-%m-%d %H:%M:%S'
        )
    
    console_handler.setFormatter(formatter)
    logger.addHandler(console_handler)
    
    return logger


class JsonFormatter(logging.Formatter):
    """JSON formatter for structured logging in production"""
    
    def format(self, record):
        log_data = {
            'timestamp': datetime.utcnow().isoformat() + 'Z',
            'level': record.levelname,
            'logger': record.name,
            'message': self.sanitize_message(record.getMessage()),
        }
        
        # Add exception info if present
        if record.exc_info:
            log_data['exception'] = self.formatException(record.exc_info)
        
        # Add extra fields if present
        if hasattr(record, 'request_id'):
            log_data['request_id'] = record.request_id
        
        return json.dumps(log_data, ensure_ascii=False)
    
    def sanitize_message(self, message):
        """Remove sensitive data from log messages"""
        import re
        # Remove potential API keys, tokens, passwords
        patterns = [
            (r'password["\']?\s*[:=]\s*["\']?[^"\'\s]+', 'password=***'),
            (r'api[_-]?key["\']?\s*[:=]\s*["\']?[^"\'\s]+', 'api_key=***'),
            (r'token["\']?\s*[:=]\s*["\']?[^"\'\s]+', 'token=***'),
            (r'secret["\']?\s*[:=]\s*["\']?[^"\'\s]+', 'secret=***'),
        ]
        
        for pattern, replacement in patterns:
            message = re.sub(pattern, replacement, message, flags=re.IGNORECASE)
        
        return message