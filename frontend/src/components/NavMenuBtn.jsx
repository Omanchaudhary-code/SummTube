const NavMenuBtn = ({ onLoginClick, onSignupClick, isMobile }) => {
  return (
    <li
      className={`flex ${
        isMobile ? "flex-col w-full px-6 gap-3" : "gap-3"
      }`}
    >
      <button
        onClick={onLoginClick}
        className={`${
          isMobile ? "w-full" : ""
        } px-4 py-2 rounded border hover:bg-gray-100 transition`}
      >
        Login
      </button>

      <button
        onClick={onSignupClick}
        className={`${
          isMobile ? "w-full" : ""
        } px-4 py-2 rounded bg-black text-white hover:opacity-90 transition`}
      >
        Sign up for free
      </button>
    </li>
  );
};

export default NavMenuBtn;
