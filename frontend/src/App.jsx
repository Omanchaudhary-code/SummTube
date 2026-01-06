import { Routes, Route } from "react-router-dom";
import Homepage from "./pages/Homepage.jsx";
import TryBoard from "./pages/TryBoard.jsx";
import Dashboard from "./pages/Dashboard.jsx";

const App = () => {
  return (
    <Routes>
      <Route path="/" element={<Homepage />} />
      <Route path="/tryboard" element={<TryBoard />} />
       <Route path="/dashboard" element={<Dashboard />} />
    </Routes>
  );
};

export default App;
