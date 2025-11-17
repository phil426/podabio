import { SVGProps } from 'react';

export function DragHandleIcon(props: SVGProps<SVGSVGElement>): JSX.Element {
  return (
    <svg
      viewBox="0 0 100 10"
      fill="none"
      stroke="currentColor"
      strokeWidth={3}
      strokeLinecap="round"
      {...props}
      aria-hidden="true"
    >
      <path d="M15 5h70" />
    </svg>
  );
}

