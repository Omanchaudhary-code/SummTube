import { useEffect, useState } from "react";
import { Navigate, useLocation } from "react-router-dom";
import api from "../services/api.js";

const ProtectedRoute = ({ children }) => {
  const [isAuthenticated, setIsAuthenticated] = useState(null);
  const [isLoading, setIsLoading] = useState(true);
  const location = useLocation();

  useEffect(() => {
    const checkAuth = async () => {
      setIsLoading(true);

      // Try to get cached user data from sessionStorage first
      const cachedUser = sessionStorage.getItem('user');
      if (cachedUser) {
        try {
          const userData = JSON.parse(cachedUser);
          // If we have cached data, assume authenticated and verify in background
          setIsAuthenticated(true);
          setIsLoading(false);

          // Verify in background (don't block UI)
          api.get("/user/profile").catch((error) => {
            // If verification fails, clear cache and re-authenticate
            if (error.response?.status === 401) {
              sessionStorage.removeItem('user');
              setIsAuthenticated(false);
            }
          });
          return;
        } catch (e) {
          // Invalid cached data, clear it
          sessionStorage.removeItem('user');
        }
      }

      // No valid cache, perform full auth check
      try {
        const response = await api.get("/user/profile");
        if (response.data.success && response.data.user) {
          setIsAuthenticated(true);
          // Cache user data in sessionStorage
          sessionStorage.setItem('user', JSON.stringify(response.data.user));
        } else {
          setIsAuthenticated(false);
          sessionStorage.removeItem('user');
        }
      } catch (error) {
        // Not authenticated
        console.error("Auth check failed:", error);
        setIsAuthenticated(false);
        sessionStorage.removeItem('user');
      } finally {
        setIsLoading(false);
      }
    };

    checkAuth();
  }, [location.pathname]); // Re-check auth when route changes

  if (isLoading) {
    // Show loading state while checking authentication
    return (
      <div className="min-h-screen w-full bg-[#181818] flex items-center justify-center">
        <div className="flex flex-col items-center gap-4">
          <div className="w-12 h-12 border-4 border-white border-t-transparent rounded-full animate-spin" />
          <p className="text-white text-lg">Loading...</p>
        </div>
      </div>
    );
  }

  if (!isAuthenticated) {
    // Redirect to homepage if not authenticated
    return <Navigate to="/" replace />;
  }

  return children;
};

export default ProtectedRoute;
