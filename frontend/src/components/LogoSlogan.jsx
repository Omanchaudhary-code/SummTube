import { Link } from "react-router-dom";
import logo from "../assets/logo.png";

const LogoSlogan = () => {
  return (
    <div className="flex items-center gap-1 sm:gap-2">
      <Link to="/">
        <img
          src={logo}
          alt="SummTube Logo"
          className="h-10 sm:h-12 w-auto object-contain"
        />
      </Link>
      <div>
        <h2 className="text-xl sm:text-2xl md:text-3xl font-bold text-[var(--text-primary)] hover:cursor-pointer">
          SummTube
        </h2>
      </div>
    </div>
  );
};

export default LogoSlogan;
