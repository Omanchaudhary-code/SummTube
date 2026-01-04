import { Link } from "react-router-dom";
import NavMenuBtn from "./NavMenuBtn";

const Menu = ({ onLoginClick, onSignupClick, isMobile }) => {
  return (
    <ul
      className={`flex ${
        isMobile
          ? "flex-col items-center gap-6 py-6"
          : "flex-row gap-8 items-center"
      }`}
    >
      <li>
        <Link to="/">
          <span className="hover:opacity-70 cursor-pointer">
            Overview
          </span>
        </Link>
      </li>

      <NavMenuBtn
        isMobile={isMobile}
        onLoginClick={onLoginClick}
        onSignupClick={onSignupClick}
      />
    </ul>
  );
};

export default Menu;
