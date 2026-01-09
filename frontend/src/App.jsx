import { Routes, Route, Navigate } from "react-router-dom";
import Homepage from "./pages/Homepage.jsx";
import TryBoard from "./pages/TryBoard.jsx";
import Dashboard from "./pages/Dashboard.jsx";

const App = () => {
  return (
    <Routes>
      <Route path="/" element={<Homepage />} />
      <Route path="/tryboard" element={<TryBoard />} />
      <Route path="/dashboard" element={<Dashboard />} />
      {/* Redirect /login to homepage since it's a modal there */}
      <Route path="/login" element={<Navigate to="/" replace />} />
    </Routes>
  );
};

export default App;
