import { Link } from "react-router-dom";

const Menu = ({ onLoginClick, onSignupClick }) => {
  return (
    <ul className="flex flex-col sm:flex-row gap-4 sm:gap-8 items-center p-4 sm:p-0">
      <li>
        <Link to="/">
          <button className="border-b-gray-950 hover:cursor-pointer">
            Overview
          </button>
        </Link>
      </li>

      {/* Login */}
      <li>
        <button
          onClick={onLoginClick}
          className="font-medium hover:border-b-1 cursor-pointer"
        >
          Login
        </button>
      </li>

      {/* Signup */}
      <li>
        <button
          onClick={onSignupClick}
          className="border border-black py-1 px-4 rounded hover:bg-black hover:text-white transition cursor-pointer"
        >
          Signup For Free
        </button>
      </li>
    </ul>
  );
};

export default Menu;
