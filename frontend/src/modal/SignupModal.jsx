// SignupModal.jsx
import { useEffect, useState } from "react";
import { HiEye, HiEyeOff } from "react-icons/hi";
import toast from "react-hot-toast";
import logo from "../assets/logo.png";
import api from "../services/api";

const SignupModal = ({
  onClose,
  onSwitchToLogin,
  variant = "default" // ✅ ADDED
}) => {
  const [form, setForm] = useState({
    name: "",
    email: "",
    password: ""
  });

  const [showPassword, setShowPassword] = useState(false);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");

  const isTryBoard = variant === "tryboard"; // ✅ ADDED

  useEffect(() => {
    document.body.style.overflow = "hidden";
    return () => (document.body.style.overflow = "auto");
  }, []);

  const handleChange = (e) => {
    setForm({ ...form, [e.target.name]: e.target.value });
    setError("");
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);
    setError("");

    try {
      await api.post("/auth/register", {
        name: form.name.trim(),
        email: form.email.trim(),
        password: form.password
      });

      toast.success("Account created successfully! Please log in.");
      setTimeout(onSwitchToLogin, 800);
    } catch (err) {
      const msg =
        err.response?.data?.message || "Something went wrong.";
      toast.error(msg);
      setError(msg);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div
      onClick={onClose}
      className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm px-3"
    >
      <div
        onClick={(e) => e.stopPropagation()}
        className={`
          w-full max-w-[450px]
          rounded-xl p-6 shadow-xl
          animate-scaleIn text-center
          ${isTryBoard ? "bg-white text-black" : "bg-white"}
        `}
      >
        <div className="flex justify-center mb-3">
          <img src={logo} alt="logo" className="w-24" />
        </div>

        <h2 className="text-2xl font-medium">
          Welcome to SummTube
        </h2>

        <p className={`mb-6 ${isTryBoard ? "text-gray-700" : "text-gray-500"}`}>
          Register with your email
        </p>

        <form onSubmit={handleSubmit}>
          <input
            name="name"
            placeholder="Full Name"
            value={form.name}
            onChange={handleChange}
            required
            className="w-full mb-3 px-3 py-2.5 border rounded"
          />

          <input
            name="email"
            type="email"
            placeholder="Email"
            value={form.email}
            onChange={handleChange}
            required
            className="w-full mb-3 px-3 py-2.5 border rounded"
          />

          <div className="relative mb-3">
            <input
              name="password"
              type={showPassword ? "text" : "password"}
              placeholder="Password"
              value={form.password}
              onChange={handleChange}
              required
              className="w-full px-3 py-2.5 border rounded pr-10"
            />
            <button
              type="button"
              onClick={() => setShowPassword(!showPassword)}
              className="absolute right-3 top-1/2 -translate-y-1/2"
            >
              {showPassword ? <HiEyeOff /> : <HiEye />}
            </button>
          </div>

          {error && (
            <p className="text-sm text-red-600 mb-3">{error}</p>
          )}

          <button
            type="submit"
            disabled={loading}
            className="w-full py-2.5 bg-black text-white rounded"
          >
            {loading ? "Creating..." : "Sign Up"}
          </button>
        </form>

        <div className="border-t mt-5 pt-4 text-sm">
          Already have an account?
          <button
            onClick={onSwitchToLogin}
            className="ml-2 font-medium"
          >
            Login
          </button>
        </div>
      </div>
    </div>
  );
};

export default SignupModal;
