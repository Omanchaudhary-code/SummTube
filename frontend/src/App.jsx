import { Routes, Route } from "react-router-dom";
import Homepage from "./pages/Homepage.jsx";
import TryBoard from "./pages/TryBoard.jsx";
import Dashboard from "./pages/Dashboard";

const App = () => {
  return (
    <Routes>
      <Route path="/" element={<Homepage />} />
      <Route path="/try" element={<TryBoard />} />
       <Route path="/dashboard" element={<Dashboard />} />
    </Routes>
  );
};

export default App;
