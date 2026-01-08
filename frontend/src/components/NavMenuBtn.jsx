const NavMenuBtn = ({ onLoginClick, onSignupClick, isMobile }) => {
  return (
    <div  // Changed from <li> to <div>
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
    </div>  // Changed from </li> to </div>
  );
};

export default NavMenuBtn;
