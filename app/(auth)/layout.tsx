export default function AuthLayout({ children }: { children: React.ReactNode }) {
  return (
    <div className="min-h-screen bg-[linear-gradient(180deg,#182131_0%,#11182a_50%,#0b1120_100%)] text-white">
      {children}
    </div>
  );
}
