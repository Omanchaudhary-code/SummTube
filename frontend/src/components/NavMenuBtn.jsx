const NavMenuBtn = ({ onLoginClick, onSignupClick }) => {
  return (
    <li className="flex gap-3">
      <button
        onClick={onLoginClick}
        className="px-4 py-2 rounded border hover:bg-gray-100"
      >
        Login
      </button>

      <button
        onClick={onSignupClick}
        className="px-4 py-2 rounded bg-black text-white hover:opacity-90"
      >
        Sign up for free
      </button>
    </li>
  );
};

export default NavMenuBtn;
