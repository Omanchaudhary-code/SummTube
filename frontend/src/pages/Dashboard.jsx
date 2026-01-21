import { useState, useEffect, useRef } from "react";
import { useNavigate } from "react-router-dom";
import { ListCollapse, Send, Menu, Download, Copy, Check, LogOut, User, Square, X, Sparkles, Zap, Clock, ExternalLink, Search, Trash2, CheckCircle2 } from "lucide-react";
import api from "../services/api.js";
import toast from "react-hot-toast";
import Avatar from "../components/Avatar.jsx";

const Dashboard = () => {
  const navigate = useNavigate();
  const [isSidebarOpen, setIsSidebarOpen] = useState(true);
  const [link, setLink] = useState("");
  const [isLoading, setIsLoading] = useState(false);
  const [summary, setSummary] = useState(null);
  const [error, setError] = useState(null);
  const [displayedText, setDisplayedText] = useState("");
  const [isGenerating, setIsGenerating] = useState(false);
  const [copied, setCopied] = useState(false);
  const [history, setHistory] = useState([]);
  const [user, setUser] = useState(null);
  const [notification, setNotification] = useState(null);
  const [searchQuery, setSearchQuery] = useState("");
  const [filteredHistory, setFilteredHistory] = useState([]);
  const [isDeleting, setIsDeleting] = useState(null);
  const abortControllerRef = useRef(null);
  const welcomedRef = useRef(false);

  // Show notification helper (using toast)
  const showNotification = (message, type = "success") => {
    if (type === "error") {
      toast.error(message);
    } else {
      toast.success(message);
    }
  };

  // Fetch user profile and history on mount
  useEffect(() => {
    const fetchData = async () => {
      try {
        // Fetch user profile
        const profileResponse = await api.get("/user/profile");
        if (profileResponse.data.success && profileResponse.data.user) {
          setUser(profileResponse.data.user);
        }
        // Fetch history
        await fetchHistory();
      } catch (error) {
        console.error("Error fetching data:", error);
        // Don't redirect here - ProtectedRoute handles authentication
      }
    };

    fetchData();
  }, []);

  useEffect(() => {
    if (user && !welcomedRef.current) {
      toast.success(`Welcome, ${user.name}!`, { position: "top-right" });
      welcomedRef.current = true;
    }
  }, [user]);
  // Simulate streaming text effect (only for newly generated summaries)
  useEffect(() => {
    if (summary?.summary && isGenerating) {
      let index = 0;
      const text = summary.summary;
      const interval = setInterval(() => {
        if (index < text.length) {
          setDisplayedText(text.slice(0, index + 1));
          index++;
        } else {
          setIsGenerating(false);
          clearInterval(interval);
        }
      }, 20);

      return () => clearInterval(interval);
    } else if (summary?.summary && !isGenerating) {
      // For loaded summaries, set displayedText immediately
      setDisplayedText(summary.summary);
    }
  }, [summary, isGenerating]);

  const fetchUserProfile = async () => {
    try {
      const response = await api.get("/user/profile");
      if (response.data.success) {
        setUser(response.data.user);
      }
    } catch (error) {
      console.error("Error fetching profile:", error);
    }
  };

  const fetchHistory = async () => {
    try {
      const response = await api.get("/summary/history");
      if (response.data.success) {
        const summaries = response.data.summaries || response.data.data || [];
        setHistory(summaries);
        setFilteredHistory(summaries);
      }
    } catch (error) {
      console.error("Error fetching history:", error);
      // Don't redirect on 401 - ProtectedRoute and api interceptor handle auth
      if (error.response?.status !== 401) {
        toast.error("Failed to load history");
      }
    }
  };

  // Filter history based on search query
  useEffect(() => {
    if (!searchQuery.trim()) {
      setFilteredHistory(history);
    } else {
      const filtered = history.filter(item =>
        (item.video_title || '').toLowerCase().includes(searchQuery.toLowerCase()) ||
        (item.summary || '').toLowerCase().includes(searchQuery.toLowerCase())
      );
      setFilteredHistory(filtered);
    }
  }, [searchQuery, history]);

  const handleDeleteSummary = async (summaryId, e) => {
    e.stopPropagation();
    if (!window.confirm("Are you sure you want to delete this summary?")) {
      return;
    }

    setIsDeleting(summaryId);
    try {
      await api.delete(`/summary/${summaryId}`);
      toast.success("Summary deleted successfully");
      fetchHistory();
      // If deleted summary is currently displayed, clear it
      if (summary?.id === summaryId) {
        setSummary(null);
        setDisplayedText("");
        setIsGenerating(false);
      }
    } catch (error) {
      toast.error("Failed to delete summary");
      console.error("Delete error:", error);
    } finally {
      setIsDeleting(null);
    }
  };

  const handleSubmit = async () => {
    if (!link.trim()) {
      showNotification("Please enter a YouTube link", "error");
      return;
    }

    setIsLoading(true);
    setError(null);
    setSummary(null);
    setDisplayedText("");
    setIsGenerating(false);

    try {
      // Create an AbortController and attach it to the request
      abortControllerRef.current = new AbortController();
      const response = await api.post(
        "/summary",
        {
          video_url: link,
          summary_type: "detailed",
        },
        { signal: abortControllerRef.current.signal }
      );

      if (response.data.success) {
        setSummary(response.data);
        setIsGenerating(true);
        setLink("");
        showNotification("Summary generated successfully!");
        fetchHistory();
      } else {
        throw new Error(response.data.error || response.data.message || "Failed to generate summary");
      }
    } catch (error) {
      // Detect abortion from axios (ERR_CANCELED) or generic abort
      const aborted = error.code === "ERR_CANCELED" || error.name === "CanceledError";
      if (aborted) {
        setError("Generation stopped by user.");
        showNotification("Generation stopped.", "error");
      } else {
        console.error("Error submitting link:", error);
        const errorMsg =
          error.response?.data?.error ||
          error.response?.data?.message ||
          error.message ||
          "Failed to generate summary. Please try again.";
        setError(errorMsg);
        showNotification(errorMsg, "error");
      }
    } finally {
      setIsLoading(false);
      abortControllerRef.current = null;
    }
  };

  const stopGeneration = () => {
    // If a request is in-flight, abort it
    if (isLoading && abortControllerRef.current) {
      try {
        abortControllerRef.current.abort();
      } catch (e) {
        // no-op if already aborted
      }
      setIsLoading(false);
    }
    // If we're typing out the summary, stop the animation
    if (isGenerating) {
      setIsGenerating(false);
      showNotification("Stopped streaming.", "error");
    }
  };

  const handleCopy = () => {
    const textToCopy = displayedText || summary?.summary || '';
    navigator.clipboard.writeText(textToCopy);
    setCopied(true);
    showNotification("Copied to clipboard!");
    setTimeout(() => setCopied(false), 2000);
  };

  const handleDownload = () => {
    if (!summary) return;
    const textToDownload = displayedText || summary.summary || '';
    if (!textToDownload) {
      showNotification("No summary content to download", "error");
      return;
    }
    const blob = new Blob([textToDownload], { type: 'text/plain' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `${summary.video_title?.replace(/[^a-z0-9]/gi, '_') || 'summary'}_summary.txt`;
    a.click();
    URL.revokeObjectURL(url);
    showNotification("Download started!");
  };

  const handleLogout = async () => {
    try {
      await api.post("/auth/logout");
      // Clear session storage on logout
      sessionStorage.removeItem('user');
      showNotification("Logged out successfully!");
      setTimeout(() => {
        navigate("/", { replace: true });
      }, 1000);
    } catch (error) {
      console.error("Logout error:", error);
      sessionStorage.removeItem('user');
      navigate("/", { replace: true });
    }
  };

  const loadHistoryItem = async (summaryId) => {
    try {
      setIsLoading(true);
      setError(null);
      const response = await api.get(`/summary/${summaryId}`);
      console.log("Load history response:", response.data); // Debug log

      if (response.data.success && response.data.data) {
        const summaryData = response.data.data;
        // Map the data to match the expected format
        // Backend returns summary_text, but frontend expects summary
        const formattedSummary = {
          id: summaryData.id,
          video_id: summaryData.video_id,
          video_url: summaryData.video_url,
          video_title: summaryData.video_title,
          thumbnail: summaryData.thumbnail,
          duration: summaryData.duration || 0,
          summary: summaryData.summary || summaryData.summary_text || '',
          summary_type: summaryData.summary_type,
          transcript_length: summaryData.transcript_length || 0,
          processing_time: summaryData.processing_time || 0,
          created_at: summaryData.created_at
        };

        console.log("Formatted summary:", formattedSummary); // Debug log

        setSummary(formattedSummary);
        // Set displayed text immediately (no typing animation for loaded summaries)
        setDisplayedText(formattedSummary.summary || '');
        setIsGenerating(false);
        setError(null);

        // Scroll to top to show the loaded summary
        setTimeout(() => {
          window.scrollTo({ top: 0, behavior: 'smooth' });
        }, 100);
      } else {
        throw new Error(response.data.error || 'Summary data not found');
      }
    } catch (error) {
      console.error("Error loading summary:", error);
      const errorMessage = error.response?.data?.error || error.response?.data?.message || error.message || "Failed to load summary";
      setError(errorMessage);
      showNotification(errorMessage, "error");
      setSummary(null);
      setDisplayedText("");
    } finally {
      setIsLoading(false);
    }
  };

  const hasStartedGeneration = summary || isLoading || error;

  return (
    <div className="min-h-screen w-full flex flex-col md:flex-row bg-gray-50 text-gray-900 overflow-hidden">
      {/* Notification Toast - Using react-hot-toast instead */}

      {/* Mobile backdrop overlay */}
      {isSidebarOpen && (
        <div
          className="fixed inset-0 bg-black/60 backdrop-blur-sm z-40 md:hidden animate-fadeIn"
          onClick={() => setIsSidebarOpen(false)}
          aria-hidden="true"
        />
      )}

      {/* LEFT SIDEBAR - Slides from left on mobile, sidebar on desktop */}
      <aside
        className={`fixed md:sticky top-0 left-0 h-full md:h-screen bg-white border-r border-gray-200 flex-shrink-0 z-50 md:z-10 shadow-2xl md:shadow-sm transition-all duration-300 ease-out ${isSidebarOpen
          ? "translate-x-0 w-80 md:w-64"
          : "-translate-x-full md:translate-x-0 md:w-16"
          }`}
      >
        <div className={`h-full flex flex-col ${isSidebarOpen ? "p-4 sm:p-5" : "p-2 md:p-3"}`}>
          {/* Logo and Toggle */}
          <div className="flex items-center justify-between mb-6 md:mb-8 flex-shrink-0">
            {isSidebarOpen ? (
              <>
                <div className="flex items-center gap-2 sm:gap-3">
                  <div className="w-10 h-10 bg-gradient-to-br from-gray-800 to-black rounded-lg flex items-center justify-center flex-shrink-0">
                    <span className="text-white font-bold text-xl">ST</span>
                  </div>
                  <span className="font-semibold text-lg text-gray-900">SummTube</span>
                </div>
                <button
                  onClick={() => setIsSidebarOpen(false)}
                  className="hover:bg-gray-100 p-2 rounded transition-colors"
                  aria-label="Close sidebar"
                >
                  <X size={20} className="text-gray-600" />
                </button>
              </>
            ) : (
              <button
                onClick={() => setIsSidebarOpen(true)}
                className="w-full flex justify-center hover:bg-gray-100 p-2 rounded transition-colors"
                aria-label="Open sidebar"
              >
                <Menu size={24} className="text-gray-600" />
              </button>
            )}
          </div>

          {/* Sidebar Content - Scrollable */}
          {isSidebarOpen && (
            <div className="flex-1 overflow-y-auto space-y-3 sm:space-y-4">
              {/* User Info */}
              {user && (
                <div className="bg-gray-50 border border-gray-200 rounded-lg p-3 sm:p-4">
                  <div className="flex items-center gap-2 sm:gap-3">
                    <Avatar user={user} size="medium" />
                    <div className="flex-1 min-w-0">
                      <p className="font-semibold text-base truncate text-gray-900">{user.name}</p>
                      <p className="text-xs text-gray-500 truncate">{user.email}</p>
                    </div>
                  </div>
                </div>
              )}

              {/* History */}
              <div className="bg-gray-50 border border-gray-200 rounded-lg p-3 sm:p-4">
                <div className="flex items-center justify-between mb-3">
                  <h3 className="font-semibold text-lg text-gray-900">Your History</h3>
                  {history.length > 0 && (
                    <span className="text-xs text-gray-600 bg-gray-200 px-2 py-1 rounded">
                      {history.length}
                    </span>
                  )}
                </div>

                {/* Search */}
                {history.length > 0 && (
                  <div className="mb-3 relative">
                    <Search className="absolute left-2 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                    <input
                      type="text"
                      value={searchQuery}
                      onChange={(e) => setSearchQuery(e.target.value)}
                      placeholder="Search..."
                      className="w-full bg-white text-gray-900 text-sm px-8 py-2 rounded-lg border border-gray-300 focus:border-gray-500 focus:outline-none"
                    />
                    {searchQuery && (
                      <button
                        onClick={() => setSearchQuery("")}
                        className="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-900"
                      >
                        <X size={16} />
                      </button>
                    )}
                  </div>
                )}

                <div className="space-y-2 max-h-48 sm:max-h-64 overflow-y-auto">
                  {filteredHistory.length > 0 ? (
                    filteredHistory.slice(0, 10).map((item) => (
                      <div
                        key={item.id}
                        onClick={() => loadHistoryItem(item.id)}
                        className="group hover:bg-gray-100 p-2 rounded cursor-pointer transition-colors relative"
                      >
                        <div className="flex items-start gap-2">
                          {item.thumbnail && (
                            <img
                              src={item.thumbnail}
                              alt={item.video_title}
                              className="w-12 h-8 object-cover rounded flex-shrink-0"
                              onError={(e) => {
                                e.target.style.display = "none";
                              }}
                            />
                          )}
                          <div className="flex-1 min-w-0">
                            <p className="text-sm truncate font-medium text-gray-900">{item.video_title || "Untitled"}</p>
                            <p className="text-xs text-gray-500">
                              {new Date(item.created_at).toLocaleDateString()}
                            </p>
                          </div>
                        </div>
                        <button
                          onClick={(e) => handleDeleteSummary(item.id, e)}
                          disabled={isDeleting === item.id}
                          className="absolute top-2 right-2 opacity-0 group-hover:opacity-100 p-1 hover:bg-red-600 rounded transition-all"
                          title="Delete summary"
                        >
                          {isDeleting === item.id ? (
                            <div className="w-3 h-3 border-2 border-gray-600 border-t-transparent rounded-full animate-spin" />
                          ) : (
                            <Trash2 size={14} className="text-gray-500 hover:text-white" />
                          )}
                        </button>
                      </div>
                    ))
                  ) : searchQuery ? (
                    <p className="text-sm text-gray-500 text-center py-4">No results found</p>
                  ) : (
                    <p className="text-sm text-gray-500 text-center py-4">No history yet</p>
                  )}
                </div>
              </div>

              {/* Features */}
              <div className="bg-gray-50 border border-gray-200 rounded-lg p-3 sm:p-4">
                <h4 className="font-semibold mb-2 text-sm text-gray-900">Features</h4>
                <ul className="space-y-2 text-xs text-gray-700">
                  <li>✓ Unlimited summaries</li>
                  <li>✓ Save your history</li>
                  <li>✓ Download summaries</li>
                  <li>✓ Priority support</li>
                </ul>
              </div>
            </div>
          )}

          {/* Logout Button */}
          {isSidebarOpen && (
            <div className="mt-3 sm:mt-4 pt-3 sm:pt-4 border-t border-gray-200 flex-shrink-0">
              <button
                onClick={handleLogout}
                className="w-full flex items-center justify-center gap-2 px-4 py-2 hover:bg-gray-100 rounded transition-colors text-red-600 hover:text-red-700 text-base"
              >
                <LogOut size={18} />
                <span>Logout</span>
              </button>
            </div>
          )}
        </div>
      </aside>

      {/* MAIN CONTENT */}
      <div className="flex-1 min-h-screen md:h-full flex flex-col overflow-hidden md:overflow-y-auto">
        {/* Top Navigation */}
        <div className="py-3 px-4 sm:py-4 sm:px-6 lg:px-10 flex items-center justify-between border-b border-gray-200 flex-shrink-0 bg-white sticky top-0 z-30">
          <div className="flex items-center gap-2 sm:gap-3">
            {/* Mobile menu button */}
            <button
              onClick={() => setIsSidebarOpen(true)}
              className="md:hidden p-2 hover:bg-gray-100 rounded-lg transition-colors"
              aria-label="Open menu"
            >
              <Menu size={20} className="text-gray-600" />
            </button>
            <h1 className="text-lg sm:text-xl md:text-2xl lg:text-3xl font-bold">Dashboard</h1>
          </div>
          <div className="flex items-center gap-2 sm:gap-3">
            {user && (
              <>
                <div className="hidden md:flex flex-col text-right mr-2">
                  <span className="text-xs text-gray-500">Welcome back,</span>
                  <span className="text-sm font-semibold text-gray-900">{user.name}</span>
                </div>
                <div className="hidden md:flex items-center gap-2">
                  <Avatar user={user} size="small" />
                  <div className="text-right">
                    <p className="text-sm font-medium text-gray-900">{user.name}</p>
                    <p className="text-xs text-gray-500">{user.email}</p>
                  </div>
                </div>
              </>
            )}
          </div>
        </div>

        {/* Content Area */}
        <div className="flex-1 overflow-y-auto relative">
          {/* Centered Input (when no summary) */}
          {!hasStartedGeneration && (
            <div className="absolute inset-0 flex items-center justify-center p-4 sm:p-6 lg:p-8">
              <div className="w-full max-w-3xl">
                <div className="text-center mb-6 sm:mb-8">
                  <h2 className="text-2xl sm:text-3xl md:text-4xl font-bold mb-3 sm:mb-4 bg-gradient-to-r from-gray-800 to-black bg-clip-text text-transparent">
                    {user ? `Welcome, ${user.name}` : "Welcome to SummTube"}
                  </h2>
                  <p className="text-gray-600 text-sm sm:text-base md:text-lg">
                    Paste a YouTube link to get an AI-generated summary
                  </p>
                </div>

                <div className="relative">
                  <div className="flex items-center gap-2 bg-white rounded-full sm:rounded-2xl py-2 sm:py-2.5 px-3 sm:px-5 shadow-lg border border-gray-300">
                    <input
                      type="text"
                      value={link}
                      onChange={(e) => setLink(e.target.value)}
                      onKeyPress={(e) => {
                        if (e.key === "Enter" && !isLoading) {
                          handleSubmit();
                        }
                      }}
                      placeholder="Paste YouTube link here..."
                      className="flex-1 px-2 sm:px-3 py-2 sm:py-3 bg-transparent text-gray-900 outline-none text-sm sm:text-base placeholder-gray-400 min-w-0"
                      disabled={isLoading}
                    />
                    {!isLoading ? (
                      <button
                        onClick={handleSubmit}
                        disabled={isLoading || !link.trim()}
                        className="bg-gradient-to-r from-gray-800 to-black text-white p-2 sm:p-3 rounded-lg hover:from-gray-900 hover:to-gray-800 transition-all disabled:opacity-50 disabled:cursor-not-allowed flex-shrink-0"
                        aria-label="Generate summary"
                      >
                        <Send size={18} className="sm:w-5 sm:h-5" />
                      </button>
                    ) : (
                      <button
                        onClick={stopGeneration}
                        className="bg-red-600 hover:bg-red-700 text-white p-2 sm:p-3 rounded-lg transition-all flex-shrink-0"
                        title="Stop generation"
                      >
                        <Square size={18} className="sm:w-5 sm:h-5" />
                      </button>
                    )}
                  </div>
                </div>
              </div>
            </div>
          )}

          {/* Summary Display Area */}
          {hasStartedGeneration && (
            <div className="p-4 md:p-6 lg:p-8 pb-32">
              <div className="max-w-4xl mx-auto">
                {error && (
                  <div className="bg-red-900 bg-opacity-30 border border-red-600 rounded-lg p-4 mb-4">
                    <p className="text-red-200">{error}</p>
                  </div>
                )}

                {isLoading && (
                  <div className="bg-white rounded-lg p-8 text-center border border-gray-200 shadow-sm">
                    <div className="flex flex-col items-center gap-4">
                      <div className="w-12 h-12 border-4 border-gray-800 border-t-transparent rounded-full animate-spin" />
                      <p className="text-gray-900 text-lg">Generating summary...</p>
                      <p className="text-gray-500 text-sm">This may take a few moments</p>
                      <button
                        onClick={stopGeneration}
                        className="mt-3 inline-flex items-center gap-2 bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg transition-all"
                        title="Stop generation"
                      >
                        <Square size={18} />
                        <span className="text-sm">Stop</span>
                      </button>
                    </div>
                  </div>
                )}

                {summary && (
                  <div className="bg-white rounded-xl p-5 sm:p-6 lg:p-8 space-y-4 sm:space-y-6 shadow-lg border border-gray-200 animate-slideDown">
                    {/* Video Info */}
                    <div className="flex flex-col sm:flex-row items-start gap-4 pb-4 sm:pb-6 border-b border-gray-200">
                      {summary.thumbnail && (
                        <div className="relative group cursor-pointer" onClick={() => summary.video_url && window.open(summary.video_url, '_blank')}>
                          <img
                            src={summary.thumbnail}
                            alt={summary.video_title}
                            className="w-full sm:w-40 md:w-48 h-32 sm:h-36 object-cover rounded-lg flex-shrink-0 transition-transform group-hover:scale-105"
                            onError={(e) => {
                              e.target.style.display = "none";
                            }}
                          />
                          <div className="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity rounded-lg flex items-center justify-center">
                            <ExternalLink className="w-5 h-5 text-white" />
                          </div>
                        </div>
                      )}
                      <div className="flex-1 min-w-0 w-full sm:w-auto">
                        <div className="flex items-start justify-between gap-2 mb-2">
                          <h3 className="text-lg sm:text-xl lg:text-2xl font-semibold break-words flex-1 text-gray-900">{summary.video_title}</h3>
                          {summary.video_url && (
                            <button
                              onClick={() => window.open(summary.video_url, '_blank')}
                              className="p-2 hover:bg-gray-100 rounded-lg transition-colors flex-shrink-0"
                              title="Open video"
                            >
                              <ExternalLink size={18} className="text-gray-600 hover:text-gray-900" />
                            </button>
                          )}
                        </div>
                        <div className="flex flex-wrap items-center gap-3 sm:gap-4 text-xs sm:text-sm text-gray-600">
                          {summary.duration && (
                            <div className="flex items-center gap-1">
                              <Clock className="w-3.5 h-3.5 sm:w-4 sm:h-4" />
                              <span>{Math.floor(summary.duration / 60)}:{String(summary.duration % 60).padStart(2, '0')}</span>
                            </div>
                          )}
                          {summary.processing_time && (
                            <>
                              <span className="hidden sm:inline">•</span>
                              <div className="flex items-center gap-1">
                                <Zap className="w-3.5 h-3.5 sm:w-4 sm:h-4" />
                                <span>{summary.processing_time}s</span>
                              </div>
                            </>
                          )}
                          {summary.transcript_length && (
                            <>
                              <span className="hidden sm:inline">•</span>
                              <span>{summary.transcript_length.toLocaleString()} chars</span>
                            </>
                          )}
                        </div>
                      </div>
                      <div className="flex gap-2 flex-shrink-0">
                        <button
                          onClick={handleCopy}
                          className="p-2 hover:bg-gray-100 rounded-lg transition-colors border border-gray-300"
                          title="Copy summary"
                        >
                          {copied ? <Check size={18} className="text-green-600" /> : <Copy size={18} className="text-gray-600" />}
                        </button>
                        <button
                          onClick={handleDownload}
                          className="p-2 hover:bg-gray-100 rounded-lg transition-colors border border-gray-300"
                          title="Download summary"
                        >
                          <Download size={18} className="text-gray-600" />
                        </button>
                      </div>
                    </div>

                    {/* Summary Content */}
                    <div>
                      <h4 className="text-base sm:text-lg font-semibold mb-3 sm:mb-4 text-gray-900 flex items-center gap-2">
                        <Sparkles className="w-4 h-4 sm:w-5 sm:h-5 text-gray-700" />
                        AI Summary
                      </h4>
                      <div className="bg-gray-50 rounded-lg p-4 sm:p-5 border border-gray-200">
                        <p className="text-sm sm:text-base text-gray-800 leading-relaxed whitespace-pre-wrap break-words">
                          {displayedText || summary?.summary || 'No summary content available'}
                          {isGenerating && (
                            <span className="inline-block w-2 h-5 bg-gray-800 ml-1 animate-pulse" />
                          )}
                        </p>
                      </div>
                      {isGenerating && (
                        <div className="mt-3 flex items-center justify-between">
                          <button
                            onClick={stopGeneration}
                            className="inline-flex items-center gap-2 bg-red-600 hover:bg-red-700 text-white px-3 py-2 rounded-md transition-all text-sm"
                            title="Stop streaming"
                          >
                            <Square size={16} />
                            <span>Stop</span>
                          </button>
                          <span className="text-xs text-gray-500">Streaming summary...</span>
                        </div>
                      )}
                    </div>

                    {/* Footer Info */}
                    {!isGenerating && (
                      <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2 text-xs sm:text-sm text-gray-600 border-t border-gray-200 pt-4">
                        <span className="flex items-center gap-1">
                          <CheckCircle2 className="w-4 h-4 text-green-600" />
                          Summary generated successfully
                        </span>
                        {summary.transcript_length && (
                          <span>Transcript: {summary.transcript_length.toLocaleString()} characters</span>
                        )}
                      </div>
                    )}
                  </div>
                )}
              </div>
            </div>
          )}
        </div>

        {/* Bottom Input (when summary exists) */}
        {hasStartedGeneration && (
          <div className="p-4 sm:p-6 border-t border-gray-200 bg-white flex-shrink-0 sticky bottom-0 z-20">
            <div className="max-w-4xl mx-auto">
              <div className="relative">
                <div className="flex items-center gap-2 bg-gray-50 rounded-full sm:rounded-2xl py-2 sm:py-2.5 px-3 sm:px-5 border border-gray-300">
                  <input
                    type="text"
                    value={link}
                    onChange={(e) => setLink(e.target.value)}
                    onKeyPress={(e) => {
                      if (e.key === "Enter" && !isLoading) {
                        handleSubmit();
                      }
                    }}
                    placeholder="Paste another YouTube link..."
                    className="flex-1 px-2 sm:px-3 py-2 sm:py-3 bg-transparent text-gray-900 outline-none text-xs sm:text-sm md:text-base placeholder-gray-400 min-w-0"
                    disabled={isLoading}
                  />
                  {!isLoading && !isGenerating ? (
                    <button
                      onClick={handleSubmit}
                      disabled={!link.trim()}
                      className="bg-gradient-to-r from-gray-800 to-black text-white p-2 sm:p-3 rounded-lg hover:from-gray-900 hover:to-gray-800 transition-all disabled:opacity-50 disabled:cursor-not-allowed flex-shrink-0"
                      aria-label="Generate summary"
                    >
                      <Send size={18} className="sm:w-5 sm:h-5" />
                    </button>
                  ) : (
                    <button
                      onClick={stopGeneration}
                      className="bg-red-600 hover:bg-red-700 text-white p-2 sm:p-3 rounded-lg transition-all flex-shrink-0"
                      title="Stop generation"
                    >
                      <Square size={18} className="sm:w-5 sm:h-5" />
                    </button>
                  )}
                </div>
              </div>
              <p className="text-xs sm:text-sm text-gray-500 mt-2 text-center px-2">
                Supported: YouTube video links
              </p>
            </div>
          </div>
        )}
      </div>
    </div>
  );
};

export default Dashboard;
