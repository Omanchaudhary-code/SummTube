import { useEffect, useState } from "react";
import { HiEye, HiEyeOff } from "react-icons/hi";
import toast from "react-hot-toast";
import logo from "../assets/Logo.png";
import api from "../services/api";

const SignupModal = ({ onClose, onSwitchToLogin }) => {
  const [form, setForm] = useState({
    name: "",
    email: "",
    password: ""
  });

  const [showPassword, setShowPassword] = useState(false);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");

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

      // ✅ SUCCESS TOAST
      toast.success("Account created successfully! Please log in.");

      // ✅ Switch to login modal after short delay
      setTimeout(() => {
        onSwitchToLogin();
      }, 800);

    } catch (err) {
      const backendMessage =
        err.response?.data?.message ||
        "Something went wrong. Please try again.";

      // ❌ ERROR TOAST
      toast.error(backendMessage);

      // Optional inline error (keeps form accessible)
      setError(backendMessage);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div
      onClick={onClose}
      className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm"
    >
      <div
        onClick={(e) => e.stopPropagation()}
        className="w-[450px] rounded-xl bg-white p-6 shadow-xl animate-scaleIn text-center"
      >
        {/* Logo */}
        <div className="flex justify-center mb-3">
          <img src={logo} alt="SummTube logo" className="w-1/4" />
        </div>

        <h2 className="text-2xl font-medium">Welcome to SummTube</h2>
        <p className="text-gray-500 mb-6">Register with your email</p>

        <form onSubmit={handleSubmit}>
          {/* Full Name */}
          <input
            type="text"
            name="name"
            placeholder="Full Name"
            value={form.name}
            onChange={handleChange}
            required
            className="w-full mb-3 px-3 py-2 border rounded focus:ring-1 focus:ring-black outline-none"
          />

          {/* Email */}
          <input
            type="email"
            name="email"
            placeholder="Email"
            value={form.email}
            onChange={handleChange}
            required
            className="w-full mb-3 px-3 py-2 border rounded focus:ring-1 focus:ring-black outline-none"
          />

          {/* Password */}
          <div className="relative mb-3">
            <input
              type={showPassword ? "text" : "password"}
              name="password"
              placeholder="Password"
              value={form.password}
              onChange={handleChange}
              required
              minLength={8}
              className="w-full px-3 py-2 border rounded focus:ring-1 focus:ring-black outline-none pr-10"
            />
            <button
              type="button"
              onClick={() => setShowPassword(!showPassword)}
              className="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500"
            >
              {showPassword ? <HiEyeOff size={20} /> : <HiEye size={20} />}
            </button>
          </div>

          {/* Inline Error (optional, safe) */}
          {error && (
            <p className="text-sm text-red-600 mb-3">{error}</p>
          )}

          {/* Submit */}
          <button
            type="submit"
            disabled={loading}
            className="w-full py-2 rounded bg-black text-white hover:bg-gray-800 transition disabled:opacity-60"
          >
            {loading ? "Creating account..." : "Sign Up"}
          </button>
        </form>

        {/* Footer */}
        <div className="border-t mt-6 pt-4 text-sm">
          <span className="mr-2 text-gray-500">
            Already have an account?
          </span>
          <button
            onClick={onSwitchToLogin}
            className="font-medium hover:text-gray-600"
          >
            Login
          </button>
        </div>
      </div>
    </div>
  );
};

export default SignupModal;
