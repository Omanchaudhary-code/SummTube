import { Link } from "react-router-dom";
import NavMenuBtn from "./NavMenuBtn";

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

      {/* Login / Signup Buttons */}
      <NavMenuBtn
        onLoginClick={onLoginClick}
        onSignupClick={onSignupClick}
      />
    </ul>
  );
};

export default Menu;
