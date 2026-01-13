import { Routes, Route, Navigate } from "react-router-dom";
import Homepage from "./pages/Homepage.jsx";
import TryBoard from "./pages/TryBoard.jsx";
import Dashboard from "./pages/Dashboard.jsx";
import NotFound from "./pages/NotFound.jsx";
import ProtectedRoute from "./components/ProtectedRoute.jsx";

const App = () => {
  return (
    <Routes>
      <Route path="/" element={<Homepage />} />
      <Route path="/tryboard" element={<TryBoard />} />
      {/* Protected Dashboard route - only logged-in users can access */}
      <Route 
        path="/dashboard" 
        element={
          <ProtectedRoute>
            <Dashboard />
          </ProtectedRoute>
        } 
      />
      {/* Redirect /login to homepage since it's a modal there */}
      <Route path="/login" element={<Navigate to="/" replace />} />
      {/* 404 - Catch all unmatched routes */}
      <Route path="*" element={<NotFound />} />
    </Routes>
  );
};

export default App;
