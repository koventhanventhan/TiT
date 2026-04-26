class League:
    def __init__(self, name: str, teams: list):
        self.name = name
        self.teams = teams
        self.matches = []

    def add_match(self, match):
        self.matches.append(match)

    def determine_winner(self):
        for team in self.teams:
            team.wins = 0
            team.league_points = 0

        for match in self.matches:
            if match.winner:
                match.winner.wins += 1
            match.team1.league_points += match.team1.points
            match.team2.league_points += match.team2.points

        # Winner is team with most wins, tie-break by total points
        winner = max(self.teams, key=lambda t: (t.wins, t.league_points))
        return winner
