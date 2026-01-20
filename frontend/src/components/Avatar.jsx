import { User } from "lucide-react";

const Avatar = ({ user, size = "medium", className = "" }) => {
    const sizeClasses = {
        small: "w-8 h-8 text-xs",
        medium: "w-10 h-10 text-sm",
        large: "w-12 h-12 text-base"
    };

    const getInitials = (name) => {
        if (!name) return "U";
        const parts = name.trim().split(" ");
        if (parts.length >= 2) {
            return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
        }
        return name.substring(0, 2).toUpperCase();
    };

    // If user has a profile picture (from Google), show it
    if (user?.profile_picture) {
        return (
            <img
                src={user.profile_picture}
                alt={user.name || "User"}
                className={`${sizeClasses[size]} rounded-full object-cover ${className}`}
                onError={(e) => {
                    // Fallback to default avatar if image fails to load
                    e.target.style.display = "none";
                    e.target.nextSibling.style.display = "flex";
                }}
            />
        );
    }

    // Default avatar with initials
    return (
        <div
            className={`${sizeClasses[size]} rounded-full bg-gradient-to-br from-gray-600 to-gray-800 flex items-center justify-center text-white font-semibold ${className}`}
        >
            {user?.name ? getInitials(user.name) : <User size={size === "small" ? 16 : size === "large" ? 24 : 20} />}
        </div>
    );
};

export default Avatar;
