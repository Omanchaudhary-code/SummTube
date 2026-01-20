import axios from "axios";

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || "https://backend-php-bbfh.onrender.com/api",
  headers: {
    "Content-Type": "application/json",
  },
  withCredentials: true,
});

// Response interceptor to handle token refresh
api.interceptors.response.use(
  (response) => response,
  async (error) => {
    const originalRequest = error.config;

    // If 401 and we haven't already tried to refresh
    if (error.response?.status === 401 && !originalRequest._retry) {
      originalRequest._retry = true;

      try {
        // Try to refresh the token
        await api.post("/auth/refresh");

        // Retry the original request (cookies will be updated automatically)
        return api(originalRequest);
      } catch (refreshError) {
        // Refresh failed - clear session storage and redirect to login
        sessionStorage.removeItem('user');
        window.location.href = "/";
        return Promise.reject(refreshError);
      }
    }

    // Clear session storage on any 401 error
    if (error.response?.status === 401) {
      sessionStorage.removeItem('user');
    }

    return Promise.reject(error);
  }
);

export default api;