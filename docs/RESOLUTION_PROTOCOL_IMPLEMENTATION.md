# Resolution Protocol - Executable Implementation

This is the agentic algorithm that executes the Resolution Protocol. The AI agent follows this structure when you say "Apply Resolution Protocol to [problem]".

## Algorithm Structure

```typescript
/**
 * RESOLUTION PROTOCOL - Agentic Problem Solving Algorithm
 * 
 * This algorithm guides the AI agent through systematic problem-solving.
 * The agent executes each phase autonomously, gathering information,
 * forming hypotheses, testing, and iterating until a solution is found.
 */

interface ProblemContext {
  description: string;
  symptoms: string[];
  errorMessages: string[];
  affectedComponents: string[];
  recentChanges: string[];
  environment: string;
}

interface Hypothesis {
  id: string;
  description: string;
  likelihood: number; // 1-10, higher = more likely
  testMethod: string;
  testable: boolean;
}

interface TestResult {
  hypothesisId: string;
  passed: boolean;
  evidence: string;
  newInformation: string[];
  nextSteps: string[];
}

interface ProtocolState {
  phase: 'gathering' | 'hypothesizing' | 'testing' | 'analyzing' | 'solving' | 'complete';
  iteration: number;
  maxIterations: number;
  problemContext: ProblemContext;
  hypotheses: Hypothesis[];
  testResults: TestResult[];
  solution: string | null;
  documentation: string[];
}

/**
 * MAIN PROTOCOL EXECUTION
 * 
 * The agent follows this flow when you invoke the protocol:
 */
async function executeResolutionProtocol(problemDescription: string): Promise<void> {
  // Initialize state
  const state: ProtocolState = {
    phase: 'gathering',
    iteration: 0,
    maxIterations: 20, // Safety limit
    problemContext: {
      description: problemDescription,
      symptoms: [],
      errorMessages: [],
      affectedComponents: [],
      recentChanges: [],
      environment: ''
    },
    hypotheses: [],
    testResults: [],
    solution: null,
    documentation: []
  };

  // PHASE 1: INFORMATION GATHERING
  // Agent autonomously collects all relevant information
  await phase1_GatherInformation(state);
  
  // PHASE 2: HYPOTHESIS FORMATION
  // Agent forms testable hypotheses based on evidence
  await phase2_FormHypotheses(state);
  
  // PHASE 3-4: TESTING LOOP
  // Agent tests hypotheses and iterates until solution found
  while (state.phase !== 'complete' && state.iteration < state.maxIterations) {
    state.iteration++;
    
    if (state.phase === 'testing') {
      await phase3_TestHypotheses(state);
    }
    
    if (state.phase === 'analyzing') {
      await phase4_AnalyzeResults(state);
    }
    
    // Check if solution found
    if (state.solution) {
      await phase5_ImplementSolution(state);
      break;
    }
  }
  
  // PHASE 5: DOCUMENTATION
  await phase6_DocumentSolution(state);
}

/**
 * PHASE 1: INFORMATION GATHERING
 * 
 * Agent autonomously gathers:
 * - Error messages and logs
 * - Code changes (git history)
 * - Configuration files
 * - Related code components
 * - Environment details
 */
async function phase1_GatherInformation(state: ProtocolState): Promise<void> {
  log('🔍 PHASE 1: Gathering Information');
  
  // 1. Extract error messages from user input
  // Agent: Parse the problem description for error messages, stack traces, etc.
  state.problemContext.errorMessages = extractErrorMessages(state.problemContext.description);
  
  // 2. Search codebase for related code
  // Agent: Use semantic search to find relevant files
  state.problemContext.affectedComponents = await searchCodebaseForRelatedCode(
    state.problemContext.description
  );
  
  // 3. Check recent git changes
  // Agent: Review git log for recent commits that might have caused this
  state.problemContext.recentChanges = await getRecentGitChanges();
  
  // 4. Gather environment information
  // Agent: Check server config, PHP version, database status, etc.
  state.problemContext.environment = await gatherEnvironmentInfo();
  
  // 5. Search for similar issues in documentation
  // Agent: Look for known issues, troubleshooting guides, etc.
  const similarIssues = await searchDocumentation(state.problemContext.description);
  
  log(`✅ Information gathered: ${state.problemContext.affectedComponents.length} components, ${state.problemContext.recentChanges.length} recent changes`);
  
  state.phase = 'hypothesizing';
}

/**
 * PHASE 2: HYPOTHESIS FORMATION
 * 
 * Agent forms testable hypotheses based on:
 * - Common patterns for this type of problem
 * - Evidence gathered in Phase 1
 * - Known issues from documentation
 * - Experience with similar problems
 */
async function phase2_FormHypotheses(state: ProtocolState): Promise<void> {
  log('💡 PHASE 2: Forming Hypotheses');
  
  // Agent: Generate hypotheses based on problem type and evidence
  const hypotheses = await generateHypotheses(state.problemContext);
  
  // Sort by likelihood (most likely first)
  hypotheses.sort((a, b) => b.likelihood - a.likelihood);
  
  state.hypotheses = hypotheses;
  
  log(`✅ Formed ${hypotheses.length} hypotheses, prioritized by likelihood`);
  log(`Top hypothesis: ${hypotheses[0].description} (likelihood: ${hypotheses[0].likelihood}/10)`);
  
  state.phase = 'testing';
}

/**
 * PHASE 3: SYSTEMATIC TESTING
 * 
 * Agent tests each hypothesis in priority order:
 * - Execute test method for hypothesis
 * - Gather evidence
 * - Log results
 * - Determine if hypothesis is confirmed or refuted
 */
async function phase3_TestHypotheses(state: ProtocolState): Promise<void> {
  log(`🧪 PHASE 3: Testing Hypotheses (Iteration ${state.iteration})`);
  
  // Get next untested hypothesis
  const hypothesis = state.hypotheses.find(h => 
    !state.testResults.some(tr => tr.hypothesisId === h.id)
  );
  
  if (!hypothesis) {
    state.phase = 'analyzing';
    return;
  }
  
  log(`Testing: ${hypothesis.description}`);
  
  // Agent: Execute the test method
  const result = await executeTest(hypothesis, state.problemContext);
  
  state.testResults.push(result);
  
  log(`Result: ${result.passed ? '✅ CONFIRMED' : '❌ REFUTED'}`);
  log(`Evidence: ${result.evidence}`);
  
  // If hypothesis confirmed, check if it's the root cause
  if (result.passed) {
    const isRootCause = await verifyRootCause(hypothesis, result, state);
    if (isRootCause) {
      state.solution = await generateSolution(hypothesis, result, state);
      state.phase = 'solving';
      return;
    }
  }
  
  // If refuted, use new information to refine hypotheses
  if (result.newInformation.length > 0) {
    log(`📊 New information gathered: ${result.newInformation.join(', ')}`);
    // Agent will use this in Phase 4 to refine hypotheses
  }
  
  state.phase = 'analyzing';
}

/**
 * PHASE 4: ANALYSIS & ITERATION
 * 
 * Agent analyzes test results:
 * - What patterns emerge?
 * - What new information was discovered?
 * - Should we refine or add new hypotheses?
 * - Are we getting closer to the root cause?
 */
async function phase4_AnalyzeResults(state: ProtocolState): Promise<void> {
  log('📊 PHASE 4: Analyzing Results');
  
  // Collect all new information from test results
  const allNewInfo = state.testResults.flatMap(tr => tr.newInformation);
  
  // Analyze patterns
  const patterns = analyzePatterns(state.testResults);
  
  log(`Patterns identified: ${patterns.length}`);
  
  // If we have new information, refine hypotheses
  if (allNewInfo.length > 0 || patterns.length > 0) {
    log('🔄 Refining hypotheses based on new evidence...');
    
    // Agent: Generate new hypotheses or update existing ones
    const refinedHypotheses = await refineHypotheses(
      state.hypotheses,
      state.testResults,
      allNewInfo,
      patterns
    );
    
    state.hypotheses = refinedHypotheses;
    log(`✅ Refined to ${refinedHypotheses.length} hypotheses`);
  }
  
  // Check if we should continue or if we're stuck
  if (state.testResults.length >= state.hypotheses.length * 2) {
    // We've tested everything multiple times, might need different approach
    log('⚠️  Multiple iterations without solution. Expanding search...');
    await expandSearch(state);
  }
  
  state.phase = 'testing';
}

/**
 * PHASE 5: SOLUTION IMPLEMENTATION
 * 
 * Agent implements the solution:
 * - Apply the fix
 * - Verify it works
 * - Check for regressions
 */
async function phase5_ImplementSolution(state: ProtocolState): Promise<void> {
  log('🔧 PHASE 5: Implementing Solution');
  
  if (!state.solution) {
    throw new Error('No solution to implement');
  }
  
  log(`Solution: ${state.solution}`);
  
  // Agent: Implement the fix
  await implementFix(state.solution, state.problemContext);
  
  // Agent: Verify the fix works
  const verified = await verifyFix(state.problemContext);
  
  if (verified) {
    log('✅ Solution verified - problem resolved!');
    state.phase = 'complete';
  } else {
    log('❌ Solution did not resolve problem. Returning to testing...');
    state.solution = null;
    state.phase = 'testing';
  }
}

/**
 * PHASE 6: DOCUMENTATION
 * 
 * Agent documents:
 * - What the problem was
 * - What the root cause was
 * - What the solution was
 * - How to prevent it in the future
 */
async function phase6_DocumentSolution(state: ProtocolState): Promise<void> {
  log('📝 PHASE 6: Documenting Solution');
  
  const documentation = {
    problem: state.problemContext.description,
    rootCause: state.testResults.find(tr => tr.passed)?.hypothesisId || 'Unknown',
    solution: state.solution || 'No solution found',
    testsPerformed: state.testResults.length,
    iterations: state.iteration,
    affectedComponents: state.problemContext.affectedComponents,
    prevention: generatePreventionTips(state)
  };
  
  state.documentation.push(JSON.stringify(documentation, null, 2));
  
  log('✅ Solution documented');
}

// ============================================================================
// HELPER FUNCTIONS (Agent Implementation Details)
// ============================================================================

/**
 * Agent uses these tools to gather information:
 * - codebase_search: Find related code
 * - grep: Search for specific patterns
 * - read_file: Examine code files
 * - run_terminal_cmd: Execute tests, check logs
 * - read_lints: Check for code errors
 */
async function searchCodebaseForRelatedCode(problemDescription: string): Promise<string[]> {
  // Agent: Use semantic search to find files related to the problem
  // Example: If problem mentions "401 errors", search for authentication code
  return await codebase_search(problemDescription);
}

async function getRecentGitChanges(): Promise<string[]> {
  // Agent: Run git log to see recent commits
  const commits = await run_terminal_cmd('git log --oneline -10');
  return commits.split('\n');
}

async function gatherEnvironmentInfo(): Promise<string> {
  // Agent: Check PHP version, server config, database status, etc.
  const phpVersion = await run_terminal_cmd('php -v');
  const serverInfo = await run_terminal_cmd('uname -a');
  return `${phpVersion}\n${serverInfo}`;
}

/**
 * Agent generates hypotheses based on:
 * - Problem type (authentication, database, API, etc.)
 * - Common causes for this type of problem
 * - Evidence gathered
 */
async function generateHypotheses(context: ProblemContext): Promise<Hypothesis[]> {
  // Agent: Analyze problem type and generate hypotheses
  // This is where the AI's knowledge comes in
  
  const hypotheses: Hypothesis[] = [];
  
  // Example: If problem is "401 errors"
  if (context.errorMessages.some(e => e.includes('401') || e.includes('Unauthorized'))) {
    hypotheses.push({
      id: 'auth-1',
      description: 'Session cookies not being sent with requests',
      likelihood: 9,
      testMethod: 'Check browser DevTools Network tab for cookies in request headers',
      testable: true
    });
    
    hypotheses.push({
      id: 'auth-2',
      description: 'Session expired or invalid',
      likelihood: 8,
      testMethod: 'Check session file existence and modification time',
      testable: true
    });
    
    hypotheses.push({
      id: 'auth-3',
      description: 'CSRF token mismatch',
      likelihood: 7,
      testMethod: 'Compare CSRF token in request vs session',
      testable: true
    });
    
    hypotheses.push({
      id: 'auth-4',
      description: 'Cookie domain/path mismatch',
      likelihood: 6,
      testMethod: 'Check session cookie domain setting vs actual domain',
      testable: true
    });
  }
  
  // Agent continues generating hypotheses based on problem type...
  
  return hypotheses;
}

/**
 * Agent executes tests programmatically where possible
 */
async function executeTest(hypothesis: Hypothesis, context: ProblemContext): Promise<TestResult> {
  // Agent: Execute the test method described in hypothesis
  
  let passed = false;
  let evidence = '';
  const newInformation: string[] = [];
  
  // Example test execution
  if (hypothesis.id === 'auth-1') {
    // Test: Check if cookies are being sent
    // Agent: Read session.php to check cookie configuration
    const sessionConfig = await read_file('includes/session.php');
    // Agent: Check if credentials: 'include' is in fetch calls
    const httpCode = await read_file('admin-ui/src/api/http.ts');
    
    if (sessionConfig.includes("cookie_samesite") && httpCode.includes("credentials: 'include'")) {
      passed = true;
      evidence = 'Session cookies configured and fetch includes credentials';
    } else {
      evidence = 'Session cookie configuration or fetch credentials may be missing';
      newInformation.push('Session configuration needs review');
    }
  }
  
  // Agent continues with other test methods...
  
  return {
    hypothesisId: hypothesis.id,
    passed,
    evidence,
    newInformation,
    nextSteps: passed ? ['Implement fix'] : ['Gather more information']
  };
}

/**
 * Agent verifies if confirmed hypothesis is actually the root cause
 */
async function verifyRootCause(
  hypothesis: Hypothesis,
  result: TestResult,
  state: ProtocolState
): Promise<boolean> {
  // Agent: Check if fixing this would resolve the problem
  // Look for other related issues that might also need fixing
  
  // If this hypothesis explains all symptoms, it's likely the root cause
  const explainsAllSymptoms = checkIfExplainsAllSymptoms(hypothesis, state.problemContext);
  
  return explainsAllSymptoms;
}

/**
 * Agent generates solution based on confirmed root cause
 */
async function generateSolution(
  hypothesis: Hypothesis,
  result: TestResult,
  state: ProtocolState
): Promise<string> {
  // Agent: Generate specific solution based on root cause
  
  // Example solutions based on hypothesis ID
  const solutions: Record<string, string> = {
    'auth-1': 'Ensure all fetch requests include credentials: "include" and session cookies are properly configured',
    'auth-2': 'Implement session refresh mechanism or extend session lifetime',
    'auth-3': 'Fix CSRF token generation/verification mismatch',
    'auth-4': 'Update session cookie domain to match current domain'
  };
  
  return solutions[hypothesis.id] || 'Solution needs to be determined based on specific root cause';
}

/**
 * Agent implements the fix
 */
async function implementFix(solution: string, context: ProblemContext): Promise<void> {
  // Agent: Apply the solution using appropriate tools
  // - Edit files if code change needed
  // - Run commands if configuration change needed
  // - Update documentation if needed
  
  log(`Implementing: ${solution}`);
  
  // Agent executes the fix based on solution description
  // This is where the AI uses its code editing tools
}

/**
 * Agent verifies the fix works
 */
async function verifyFix(context: ProblemContext): Promise<boolean> {
  // Agent: Test that the problem is resolved
  // - Run the same tests that showed the problem
  // - Check logs for errors
  // - Verify functionality works
  
  log('Verifying fix...');
  
  // Agent: Execute verification tests
  // Return true if problem is resolved, false otherwise
  
  return true; // Placeholder - agent implements actual verification
}

// ============================================================================
// LOGGING & TRACEABILITY
// ============================================================================

/**
 * All actions are logged for traceability
 */
function log(message: string): void {
  const timestamp = new Date().toISOString();
  console.log(`[${timestamp}] ${message}`);
  // Agent: Also write to protocol log file
}

/**
 * Agent maintains a protocol log file for each problem
 */
async function writeProtocolLog(state: ProtocolState): Promise<void> {
  const logContent = {
    problem: state.problemContext.description,
    startTime: new Date().toISOString(),
    iterations: state.iteration,
    hypotheses: state.hypotheses,
    testResults: state.testResults,
    solution: state.solution,
    endTime: new Date().toISOString()
  };
  
  // Agent: Write to docs/debug-logs/resolution-protocol-[timestamp].json
}

// ============================================================================
// USAGE INSTRUCTIONS FOR AI AGENT
// ============================================================================

/**
 * WHEN USER SAYS: "Apply Resolution Protocol to [problem]"
 * 
 * The AI agent should:
 * 
 * 1. Acknowledge: "🔍 RESOLUTION PROTOCOL ACTIVATED - [problem description]"
 * 
 * 2. Execute Phase 1: Gather Information
 *    - Use codebase_search to find related code
 *    - Use grep to find error patterns
 *    - Read relevant files
 *    - Check git history
 *    - Gather environment info
 * 
 * 3. Execute Phase 2: Form Hypotheses
 *    - Based on problem type, generate 5-10 hypotheses
 *    - Prioritize by likelihood
 *    - Make each testable
 * 
 * 4. Execute Phase 3-4 Loop: Test & Analyze
 *    - Test each hypothesis systematically
 *    - Log all results
 *    - Analyze patterns
 *    - Refine hypotheses based on new evidence
 *    - Repeat until root cause found
 * 
 * 5. Execute Phase 5: Implement Solution
 *    - Apply the fix
 *    - Verify it works
 *    - Check for regressions
 * 
 * 6. Execute Phase 6: Document
 *    - Record problem, cause, solution
 *    - Add to troubleshooting guide if needed
 * 
 * 7. Report: "✅ RESOLUTION PROTOCOL COMPLETE - [summary]"
 * 
 * IMPORTANT: The agent should work autonomously through all phases,
 * only asking the user for clarification if absolutely necessary.
 * The agent should log all actions and explain what it's doing at each step.
 */

