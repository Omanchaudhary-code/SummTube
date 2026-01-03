import { Routes, Route } from "react-router-dom";
import Homepage from "./pages/Homepage.jsx";
import TryBoard from "./pages/TryBoard.jsx";

const App = () => {
  return (
    <Routes>
      <Route path="/" element={<Homepage />} />
      <Route path="/try" element={<TryBoard />} />
    </Routes>
  );
};

export default App;
