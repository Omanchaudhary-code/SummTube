import { useEffect, useState } from "react";
import { useNavigate, useLocation } from "react-router-dom";
import { Home, ArrowLeft, Search, AlertCircle } from "lucide-react";

const NotFound = () => {
  const navigate = useNavigate();
  const location = useLocation();
  const [mounted, setMounted] = useState(false);

  useEffect(() => {
    setMounted(true);
  }, []);

  return (
    <div className="min-h-screen w-full bg-[#181818] text-white flex items-center justify-center p-4 overflow-hidden relative">
      {/* Animated background elements */}
      <div className="absolute inset-0 overflow-hidden pointer-events-none">
        <div className={`absolute top-1/4 left-1/4 w-64 h-64 bg-white/5 rounded-full blur-3xl transition-all duration-1000 ${mounted ? 'opacity-100 scale-100' : 'opacity-0 scale-0'}`} />
        <div className={`absolute bottom-1/4 right-1/4 w-96 h-96 bg-white/5 rounded-full blur-3xl transition-all duration-1000 delay-300 ${mounted ? 'opacity-100 scale-100' : 'opacity-0 scale-0'}`} />
        <div className={`absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-48 h-48 bg-white/5 rounded-full blur-3xl transition-all duration-1000 delay-500 ${mounted ? 'opacity-100 scale-100' : 'opacity-0 scale-0'}`} />
      </div>

      <div className={`relative z-10 text-center max-w-2xl mx-auto transition-all duration-700 ${mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-10'}`}>
        {/* 404 Number with animation */}
        <div className="mb-8">
          <h1 className={`text-8xl sm:text-9xl md:text-[12rem] font-bold bg-gradient-to-br from-white via-gray-300 to-gray-500 bg-clip-text text-transparent transition-all duration-1000 ${mounted ? 'opacity-100 scale-100' : 'opacity-0 scale-50'}`}>
            404
          </h1>
        </div>

        {/* Error Icon */}
        <div className={`mb-6 flex justify-center transition-all duration-700 delay-200 ${mounted ? 'opacity-100 scale-100 rotate-0' : 'opacity-0 scale-0 rotate-180'}`}>
          <div className="w-20 h-20 sm:w-24 sm:h-24 rounded-full bg-white/10 border-2 border-white/20 flex items-center justify-center backdrop-blur-sm">
            <AlertCircle className="w-10 h-10 sm:w-12 sm:h-12 text-white" />
          </div>
        </div>

        {/* Error Message */}
        <div className={`mb-8 transition-all duration-700 delay-300 ${mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-5'}`}>
          <h2 className="text-2xl sm:text-3xl md:text-4xl font-bold mb-4">
            Page Not Found
          </h2>
          <p className="text-gray-400 text-base sm:text-lg mb-2">
            Oops! The page you're looking for doesn't exist.
          </p>
          <p className="text-gray-500 text-sm sm:text-base">
            <code className="bg-white/10 px-2 py-1 rounded text-gray-300 font-mono">
              {location.pathname}
            </code>
          </p>
        </div>

        {/* Action Buttons */}
        <div className={`flex flex-col sm:flex-row items-center justify-center gap-4 transition-all duration-700 delay-500 ${mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-5'}`}>
          <button
            onClick={() => navigate("/")}
            className="group flex items-center gap-2 px-6 py-3 bg-white text-[#181818] rounded-lg font-semibold hover:bg-gray-200 transition-all transform hover:scale-105 active:scale-95 shadow-lg hover:shadow-xl"
          >
            <Home className="w-5 h-5 group-hover:scale-110 transition-transform" />
            <span>Go Home</span>
          </button>
          
          <button
            onClick={() => navigate(-1)}
            className="group flex items-center gap-2 px-6 py-3 bg-white/10 text-white border border-white/20 rounded-lg font-semibold hover:bg-white/20 transition-all transform hover:scale-105 active:scale-95 backdrop-blur-sm"
          >
            <ArrowLeft className="w-5 h-5 group-hover:-translate-x-1 transition-transform" />
            <span>Go Back</span>
          </button>
        </div>

        {/* Additional Help Text */}
        <div className={`mt-12 text-sm text-gray-500 transition-all duration-700 delay-700 ${mounted ? 'opacity-100' : 'opacity-0'}`}>
          <p>If you believe this is an error, please contact support.</p>
        </div>
      </div>

      {/* Floating particles animation */}
      <div className="absolute inset-0 pointer-events-none overflow-hidden">
        {[...Array(20)].map((_, i) => (
          <div
            key={i}
            className={`absolute w-1 h-1 bg-white/20 rounded-full transition-all duration-3000 ${mounted ? 'opacity-100' : 'opacity-0'}`}
            style={{
              left: `${Math.random() * 100}%`,
              top: `${Math.random() * 100}%`,
              animationDelay: `${Math.random() * 2}s`,
              animation: 'float 6s ease-in-out infinite'
            }}
          />
        ))}
      </div>

      <style>{`
        @keyframes float {
          0%, 100% {
            transform: translateY(0) translateX(0);
            opacity: 0.2;
          }
          50% {
            transform: translateY(-20px) translateX(10px);
            opacity: 0.5;
          }
        }
      `}</style>
    </div>
  );
};

export default NotFound;
